<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface,
    Imbo\Exception\TransformationException,
    Imbo\Storage\ImageReaderAware,
    Imbo\Storage\ImageReader,
    Imbo\Image\Transformation\TransformationInterface,
    Imbo\Model\Image;

/**
 * Image transformer listener
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Event\Listeners
 */
class ImageTransformer implements ListenerInterface {
    /**
     * An array of registered transformation handlers
     *
     * @var array
     */
    private $transformationHandlers = array();

    /**
     * An image reader instance
     *
     * @var ImageReader
     */
    private $imageReader;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return array(
            // Initialize the image transformations from the configuration
            'image.get' => array('initialize' => 1),

            // Transform the image
            'image.transform' => 'transform',
        );
    }

    /**
     * Initialize the transformer
     *
     * @param EventInterface $event The current event
     */
    public function initialize(EventInterface $event) {
        foreach ($event->getConfig()['imageTransformations'] as $name => $callback) {
            $this->registerTransformationHandler($name, $callback);
        }
    }

    /**
     * Transform images
     *
     * @param EventInterface $event The current event
     */
    public function transform(EventInterface $event) {
        $request = $event->getRequest();
        $image = $event->getResponse()->getModel();
        $transformed = false;

        // Fetch and apply transformations
        foreach ($request->getTransformations() as $transformation) {
            $name = $transformation['name'];
            $params = $transformation['params'];
            $transformation = null;

            if (!isset($this->transformationHandlers[$name])) {
                throw new TransformationException('Unknown transformation: ' . $name, 400);
            }

            $callback = $this->transformationHandlers[$name];

            if (is_string($callback)) {
                $transformation = new $callback($params);
            } else if (is_callable($callback)) {
                $transformation = $callback($params);
            }

            if (!($transformation instanceof TransformationInterface)) {
                throw new TransformationException('Invalid image transformation: ' . $name, 500);
            }

            if ($transformation instanceof ImageReaderAware) {
                $transformation->setImageReader($this->getImageReader($event));
            }

            $transformation->applyToImage($image);
            $transformed = true;
        }

        $image->hasBeenTransformed($transformed);
    }

    /**
     * Register an image transformation handler
     *
     * @param string $name The name of the transformation, as used in the query parameters
     * @param callable $callback A piece of code that can be executed. The callback will receive a
     *                           single parameter: $params, which is an array with parameters
     *                           associated with the transformation. The callable must return an
     *                           instance of Imbo\Image\Transformation\TransformationInterface
     * @return ImageTransformer
     */
    public function registerTransformationHandler($name, $callback) {
        $this->transformationHandlers[$name] = $callback;

        return $this;
    }

    /**
     * Fetch an instance of an image reader
     *
     * @param  EventInterface $event The event that triggered the transformation
     * @return ImageReader An image reader instance
     */
    private function getImageReader(EventInterface $event) {
        if (!$this->imageReader) {
            $publicKey = $event->getRequest()->getPublicKey();
            $storage = $event->getStorage();

            $this->imageReader = new ImageReader($publicKey, $storage);
        }

        return $this->imageReader;
    }
}
