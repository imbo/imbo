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
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return array(
            'image.transform' => array(
                'transform' => 0,
                'initialize' => 10,
            ),
        );
    }

    /**
     * Initialize the transformer
     *
     * @param EventInterface $event The current event
     */
    public function initialize(EventInterface $event) {
        $request = $event->getRequest();
        $imageReader = new ImageReader($request->getPublicKey(), $event->getStorage());

        $image = $event->getResponse()->getModel();
        $image->setImageReader($imageReader);

        foreach ($event->getConfig()['imageTransformations'] as $name => $transformation) {
            $image->setTransformationHandler($name, $transformation);
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

        // Fetch transformations specifed in the query and transform the image
        foreach ($request->getTransformations() as $transformation) {
            $image->transform($transformation['name'], $transformation['params']);
        }
    }
}
