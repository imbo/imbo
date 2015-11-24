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
    Imbo\EventListener\Initializer\InitializerInterface;

/**
 * Image transformation manager
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Event\Manager
 */
class TransformationManager {
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
}
