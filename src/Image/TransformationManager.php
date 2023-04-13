<?php declare(strict_types=1);
namespace Imbo\Image;

use Imbo\EventListener\Initializer\InitializerInterface;
use Imbo\EventListener\ListenerInterface;
use Imbo\EventManager\EventInterface;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;
use Imbo\Image\Transformation\Transformation;
use Imbo\Model\Image;

class TransformationManager implements ListenerInterface
{
    /**
     * Uninitialized image transformations
     *
     * @var array<string,TLiteralClassString|Closure:Transformation>
     */
    protected array $transformations = [];

    /**
     * Initialized transformation handlers
     *
     * @var array<string,Transformation>
     */
    protected array $handlers = [];

    /**
     * Image transformation initializers
     *
     * @var array<InitializerInterface>
     */
    protected array $initializers = [];

    /**
     * Track if the manager has attempted to apply transformations.
     */
    protected bool $transformationsApplied = false;

    public static function getSubscribedEvents(): array
    {
        return [
            'image.transform' => 'applyTransformations',

            // Adjust transformations so that crop coordinates (and other parameters) works on an
            // input image of a different size than the original, for instance when the image
            // variations listener is enabled
            'image.transformations.adjust' => 'adjustImageTransformations',

            'image.loaded' => ['adjustImageTransformations' => 50],
        ];
    }

    /**
     * Add a transformation to the manager
     *
     * @param TLiteralClassString|Closure:Transformation|Transformation $transformation Class name, Transformation instance or callable that returns one
     */
    public function addTransformation(string $name, $transformation): self
    {
        if ($transformation instanceof Transformation) {
            $this->handlers[$name] = $transformation;
        } else {
            $this->transformations[$name] = $transformation;
        }

        return $this;
    }

    /**
     * Add a transformation to the manager
     *
     * @param array<string,TLiteralClassString|Closure:Transformation|Transformation> $transformations Array of transformations, keys being the transformation names
     */
    public function addTransformations(array $transformations): self
    {
        foreach ($transformations as $name => $transformation) {
            $this->addTransformation($name, $transformation);
        }

        return $this;
    }

    /**
     * Add an event listener/transformation initializer
     */
    public function addInitializer(InitializerInterface $initializer): self
    {
        $this->initializers[] = $initializer;
        return $this;
    }

    /**
     * Get the transformation registered for the given transformation name
     *
     * @return Transformation|false
     */
    public function getTransformation(string $name)
    {
        if (isset($this->handlers[$name])) {
            return $this->handlers[$name];
        } elseif (!isset($this->transformations[$name]) || !$this->transformations[$name]) {
            return false;
        }

        // The listener has not been initialized
        $transformation = $this->transformations[$name];

        if (is_callable($transformation) && !($transformation instanceof Transformation)) {
            $transformation = $transformation();
        }

        if (is_string($transformation)) {
            $transformation = new $transformation();
        }

        foreach ($this->initializers as $initializer) {
            $initializer->initialize($transformation);
        }

        $this->handlers[$name] = $transformation;

        return $this->handlers[$name];
    }

    /**
     * Apply image transformations
     */
    public function applyTransformations(EventInterface $event): void
    {
        $request = $event->getRequest();
        $image = $event->getResponse()->getModel();
        $presets = $event->getConfig()['transformationPresets'];

        // Fetch transformations specifed in the query and transform the image
        foreach ($request->getTransformations() as $transformation) {
            if (isset($presets[$transformation['name']])) {
                // Preset
                foreach ($presets[$transformation['name']] as $name => $params) {
                    if (is_int($name)) {
                        // No hardcoded params, use the ones from the request
                        $name = $params;
                        $params = $transformation['params'];
                    } else {
                        // Some hardcoded params. Merge with the ones from the request, making the
                        // hardcoded params overwrite the ones from the request
                        $params = array_replace($transformation['params'], $params);
                    }

                    $this->triggerTransformation($name, $params, $event);
                }
            } else {
                // Regular transformation
                $this->triggerTransformation(
                    $transformation['name'],
                    $transformation['params'],
                    $event,
                );
            }

            $this->transformationsApplied = true;
        }
    }

    /**
     * Get the minimum size of the original image that we can accept, based on the transformations
     * present in the request query string. For instance, if we have an image that is 10 000 pixels
     * in width, and we have applied a single `maxSize`-transformation with a width of 1000 pixels,
     * we could in theory use an image that is down to 1000 pixels wide as input, as opposed to the
     * original image which might take significantly longer to resize.
     *
     * Returns an array consisting of keys: `width`, `height` and `index`, where `index` is the
     * index of the transformation that determined the minimum input size, in the end. This is used
     * in cases where we need to adjust the transformation parameters to account for the new size
     * of the input image.
     *
     * @throws InvalidArgumentException
     * @return array{width:int,height:int,index:int}|false `false` if we need the full size of the input image, array otherwise
     */
    public function getMinimumImageInputSize(EventInterface $event): array|false
    {
        $transformations = $event->getRequest()->getTransformations();

        if (empty($transformations)) {
            return false;
        }

        /** @var Image */
        $image = $event->getResponse()->getModel();
        $region = null;

        $flipDimensions = false;
        $minimum = ['width' => $image->getWidth(), 'height' => $image->getHeight(), 'index' => 0];
        $inputSize = ['width' => $image->getWidth(), 'height' => $image->getHeight()];

        foreach ($transformations as $i => $transformation) {
            $params = $transformation['params'];

            $handler = $this->getTransformation($transformation['name']);

            // Some transformations, such as `crop`, will return a region of the input image.
            // In some cases, we'll need the full size of the image to extract this properly,
            // but in other cases we can make do with a smaller version. We only fetch the
            // first region that is requested, as this will determine the minimum input size
            if (!$region && $handler instanceof RegionExtractor) {
                $region = $handler->setImage($image)->getExtractedRegion($params, $inputSize);

                // RegionExtractors return false if no region is extracted
                if ($region) {
                    $minimum['index'] = $i;
                }
            }

            if ($handler instanceof InputSizeConstraint) {
                $minSize = $handler->setImage($image)->getMinimumInputSize($params, $inputSize);

                if ($minSize === InputSizeConstraint::NO_TRANSFORMATION) {
                    continue;
                } elseif ($minSize === InputSizeConstraint::STOP_RESOLVING) {
                    break;
                } elseif (!is_array($minSize)) {
                    throw new InvalidArgumentException('Invalid return value from getMinimumInputSize', Response::HTTP_INTERNAL_SERVER_ERROR);
                }

                // Check that the calculated value contains a width (some only return rotation)
                if (isset($minSize['width'])) {
                    // Check if the output size of this transformation is larger than our current
                    $isThinner = $minSize['width'] < $minimum['width'];
                    $isLower = $minSize['height'] < $minimum['height'];

                    if ($isThinner || $isLower) {
                        $minimum['width'] = $minSize['width'];
                        $minimum['height'] = $minSize['height'];

                        // Any region that has been found will determine the size in the end,
                        // do not override the index in such cases
                        if (!$region) {
                            $minimum['index'] = $i;
                        }
                    }
                }

                // Some transformation might rotate the image. If it yields an angle that is
                // divisable by 90, but not by 180, exchange the values provided as width/height
                // for the next transformations in the chain
                $rotation = isset($minSize['rotation']) ? $minSize['rotation'] : false;
                if ($rotation && $rotation % 180 !== 0 && $rotation % 90 === 0) {
                    $inputSize = [
                        'width'  => $inputSize['height'],
                        'height' => $inputSize['width'],
                    ];

                    $flipDimensions = !$flipDimensions;
                }
            }
        }

        // If region has been found, calculate input size based on original aspect ratio
        if ($region && $minimum['width'] > 0) {
            $originalRatio = $image->getWidth() / $image->getHeight();
            $regionRatio = $image->getWidth() / $region['width'];

            $minimum['width'] = $minimum['width'] * $regionRatio;
            $minimum['height'] = $minimum['width'] / $originalRatio;
        } elseif ($flipDimensions) {
            $minimum = [
                'width'  => $minimum['height'],
                'height' => $minimum['width'],
                'index'  => $minimum['index'],
            ];
        }

        // Return false if the input size is larger than the original
        $widerThanOriginal = $minimum['width'] >= $image->getWidth();
        $higherThanOriginal = $minimum['height'] >= $image->getHeight();
        if ($widerThanOriginal || $higherThanOriginal) {
            return false;
        }

        return [
            'width'  => (int) ceil($minimum['width']),
            'height' => (int) ceil($minimum['height']),
            'index'  => $minimum['index'],
        ];
    }

    /**
     * Adjust image transformations
     */
    public function adjustImageTransformations(EventInterface $event): void
    {
        // If the image has not stepped through any input size transformations,
        // we don't set any ratio, and it should be safe to assume no transformations
        // parameters require any adjustment
        if (!$event->hasArgument('ratio')) {
            return;
        }

        $request = $event->getRequest();
        $transformations = $request->getTransformations();

        $ratio = $event->getArgument('ratio');
        $transformationIndex = $event->getArgument('transformationIndex');

        // Adjust coordinates according to the ratio between the original and the variation
        for ($i = 0; $i <= $transformationIndex; $i++) {
            $name = $transformations[$i]['name'];
            $params = $transformations[$i]['params'];
            $handler = $this->getTransformation($name);

            if ($handler instanceof InputSizeConstraint) {
                $params = $handler->adjustParameters($ratio, $params);

                $transformations[$i]['params'] = $params;
            }
        }

        $request->setTransformations($transformations);
    }

    /**
     * Trigger transformation with the given name, with the given parameters
     *
     * @param array $params Transformation parameters
     * @throws TransformationException If the transformation fails or is not registered
     */
    protected function triggerTransformation(string $name, array $params, EventInterface $event)
    {
        $transformation = $this->getTransformation($name);

        if (!$transformation) {
            throw new TransformationException('Transformation "' . $name . '" not registered', Response::HTTP_BAD_REQUEST);
        }

        $transformation
            ->setImage($event->getResponse()->getModel())
            ->setEvent($event)
            ->transform($params);
    }

    /**
     * Check whether the manager has attempted to apply transformations (i.e. transformations are present in the pipeline).
     *
     * @return bool Whether transformations has been triggered
     */
    public function hasAppliedTransformations(): bool
    {
        return $this->transformationsApplied;
    }
}
