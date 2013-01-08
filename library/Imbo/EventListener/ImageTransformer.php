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
    Imbo\Container,
    Imbo\ContainerAware,
    Imbo\Image\Transformation\TransformationInterface,
    Imbo\Image\Image;

/**
 * Image transformer listener
 *
 * @package EventListener
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class ImageTransformer implements ContainerAware, ListenerInterface {
    /**
     * Service container
     *
     * @var Container
     */
    private $container;

    /**
     * An array of registered transformation handlers
     *
     * @var array
     */
    private $transformationHandlers = array();

    /**
     * {@inheritdoc}
     */
    public function setContainer(Container $container) {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition() {
        return array(
            new ListenerDefinition('image.transform', array($this, 'transform')),
        );
    }

    /**
     * Transform images
     *
     * @param EventInterface $event The current event
     */
    public function transform(EventInterface $event) {
        $request = $event->getRequest();
        $image = $event->getResponse()->getImage();
        $transformed = false;

        // Fetch and apply transformations
        foreach ($request->getTransformations() as $transformation) {
            $name = $transformation['name'];

            if (!isset($this->transformationHandlers[$name])) {
                throw new TransformationException('Unknown transformation: ' . $name, 400);
            }

            $callback = $this->transformationHandlers[$name];
            $transformation = $callback($transformation['params']);

            if ($transformation instanceof TransformationInterface) {
                $transformation->applyToImage($image);
            } else if (is_callable($transformation)) {
                $transformation($image);
            }

            $transformed = true;
        }

        // See if we want to trigger a conversion. This happens if the user agent has specified an
        // image type in the URI, or if the user agent does not accept the original content type of
        // the requested image.
        $extension = $request->getExtension();
        $imageType = $image->getMimeType();
        $acceptableTypes = $request->getAcceptableContentTypes();
        $contentNegotiation = $this->container->get('contentNegotiation');

        if (!$extension && !$contentNegotiation->isAcceptable($imageType, $acceptableTypes)) {
            $typesToCheck = Image::$mimeTypes;

            $match = $contentNegotiation->bestMatch(array_keys($typesToCheck), $acceptableTypes);

            if (!$match) {
                throw new TransformationException('Not Acceptable', 406);
            }

            if ($match !== $imageType) {
                // The match is of a different type than the original image
                $extension = $typesToCheck[$match];
            }
        }

        if ($extension) {
            // Trigger a conversion
            $callback = $this->transformationHandlers['convert'];

            $convert = $callback(array('type' => $extension));
            $convert->applyToImage($image);

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
}
