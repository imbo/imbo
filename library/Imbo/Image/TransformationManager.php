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
