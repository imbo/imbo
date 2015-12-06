<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Image;

use Imbo\Image\Transformation\Transformation,
    Imbo\Exception\TransformationException,
    Imbo\EventManager\EventInterface,
    Imbo\EventListener\Initializer\InitializerInterface,
    Imbo\EventListener\ListenerInterface;

/**
 * Image transformation manager
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Event\Manager
 */
class TransformationManager implements ListenerInterface {
    /**
     * Uninitialized image transformations
     *
     * @var array
     */
    protected $transformations = [];

    /**
     * Initialized transformation handlers
     *
     * @var array
     */
    protected $handlers = [];

    /**
     * Image transformation initializers
     *
     * @var array
     */
    protected $initializers = [];

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'image.transform' => 'applyTransformations',
        ];
    }

    /**
     * Add a transformation to the manager
     *
     * @param string $name Name of the transformation
     * @param mixed $transformation Class name, Transformation instace or callable that returns one
     * @return self
     */
    public function addTransformation($name, $transformation) {
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
     * @param array $transformations Array of transformations, keys being the transformation names
     * @return self
     */
    public function addTransformations(array $transformations) {
        foreach ($transformations as $name => $transformation) {
            $this->addTransformation($name, $transformation);
        }

        return $this;
    }

    /**
     * Add an event listener/transformation initializer
     *
     * @param InitializerInterface $initializer An initializer instance
     * @return self
     */
    public function addInitializer(InitializerInterface $initializer) {
        $this->initializers[] = $initializer;

        return $this;
    }

    /**
     * Get the transformation registered for the given transformation name
     *
     * @param string $name Name of transformation
     * @return Transformation
     */
    public function getTransformation($name) {
        if (isset($this->handlers[$name])) {
            return $this->handlers[$name];
        } else if (!isset($this->transformations[$name]) || !$this->transformations[$name]) {
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
     *
     * @param EventInterface $event The current event
     */
    public function applyTransformations(EventInterface $event) {
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
                    $event
                );
            }
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
     * @param  EventInterface $event The event that triggered this calculation
     * @return array|boolean `false` if we need the full size of the input image, array otherwise
     */
    public function getMinimumImageInputSize(EventInterface $event) {
        $transformations = $event->getRequest()->getTransformations();

        if (empty($transformations)) {
            return false;
        }

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

                // Transformations can return `null` if no transformation will occur,
                // or `false` if we should stop the minimum input size resolving because
                // the transformation can't be predicted or calculated in advance
                // (for instance if it needs access to the Imagick instance)
                if ($minSize === null) {
                    continue;
                } else if ($minSize === false) {
                    break;
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
        } else if ($flipDimensions) {
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
     * Trigger transformation with the given name, with the given parameters
     *
     * @param string $name Name of transformation
     * @param array $params Transformation parameters
     * @param EventInterface $event Event that triggered the transformation chain
     * @throws TransformationException If the transformation fails or is not registered
     */
    protected function triggerTransformation($name, array $params, EventInterface $event) {
        $transformation = $this->getTransformation($name);

        if (!$transformation) {
            throw new TransformationException('Transformation "' . $name . '" not registered', 400);
        }

        $transformation
            ->setImage($event->getResponse()->getModel())
            ->setEvent($event)
            ->transform($params);
    }
}
