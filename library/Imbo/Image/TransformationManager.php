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

    public function getMinimumImageInputSize(EventInterface $event) {
        $transformations = $event->getRequest()->getTransformations();
        $image = $event->getResponse()->getModel();

        $minimum = ['width' => 0, 'height' => 0];
        foreach ($transformations as $i => $transformation) {
            $params = $transformation['params'];

            $handler = $this->getTransformation($transformation['name']);
            if ($handler instanceof InputSizeAware) {
                $minSize = $handler->setImage($image)->getMinimumInputSize($params);

                if (!$minSize) {
                    continue;
                }

                if ($minimum['width']  < $minSize['width'] ||
                    $minimum['height'] < $minSize['height']) {
                    $minimum = $minSize;
                }
            }
        }

        // Return false if the input size is either zero or the size is larger than the original
        if (!$minimum['width'] || !$minimum['height'] ||
            $minimum['width'] > $image->getWidth() || $minimum['height'] > $image->getHeight()) {
            return false;
        }

        return array_map('intval', array_map('ceil', $minimum));

        // Possible widths to use
        $widths = [];

        // Extracts from the image
        $extracts = [];

        // Calculate the aspect ratio in case some transformations only specify height
        $ratio = $width / $height;

        foreach ($transformations as $i => $transformation) {
            $name = $transformation['name'];
            $params = $transformation['params'];

            if ($name === 'maxSize') {
                // MaxSize transformation
                if (isset($params['width'])) {
                    // width detected
                    $widths[$i] = (int) $params['width'];
                } else if (isset($params['height'])) {
                    // height detected, calculate ratio
                    $widths[$i] = (int) $params['height'] * $ratio;
                }
            } else if ($name === 'resize') {
                // Resize transformation
                if (isset($params['width'])) {
                    // width detected
                    $widths[$i] = (int) $params['width'];
                } else if (isset($params['height'])) {
                    // height detected, calculate ratio
                    $widths[$i] = (int) $params['height'] * $ratio;
                }
            } else if ($name === 'thumbnail') {
                // Thumbnail transformation
                if (isset($params['width'])) {
                    // Width have been specified
                    $widths[$i] = (int) $params['width'];
                } else if (isset($params['height']) && isset($params['fit']) && $params['fit'] === 'inset') {
                    // Height have been specified, and the fit mode is inset, calculate width
                    $widths[$i] = (int) $params['height'] * $ratio;
                } else {
                    // No width or height/inset fit combo. Use default width for thumbnails
                    $widths[$i] = 50;
                }
            } else if ($name === 'crop' && empty($widths)) {
                // Crop transformation
                $extracts[$i] = $params;
            }
        }

        if ($widths && !empty($extracts)) {
            // If we are fetching extracts, we need a larger version of the image
            $extract = reset($extracts);

            // Find the correct scaling factor for the extract
            $extractFactor = $width / $extract['width'];
            $maxWidth = max($widths);

            // Find the new max width
            $maxWidth = $maxWidth * $extractFactor;

            return [key($extracts) => $maxWidth];
        }

        if ($widths) {
            // Find the max width in the set, and return it along with the index of the
            // transformation that first referenced it
            $maxWidth = max($widths);

            return [array_search($maxWidth, $widths) => $maxWidth];
        }

        return null;
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
