<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Resource;

use Imbo\EventManager\EventInterface,
    Imbo\Model;

/**
 * Image resource
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Resources
 */
class Image implements ResourceInterface {
    /**
     * {@inheritdoc}
     */
    public function getAllowedMethods() {
        return ['GET', 'HEAD', 'DELETE'];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'image.get' => 'getImage',
            'image.head' => 'getImage',
            'image.delete' => 'deleteImage',
        ];
    }

    /**
     * Handle DELETE requests
     *
     * @param EventInterface
     */
    public function deleteImage(EventInterface $event) {
        $event->getManager()->trigger('db.image.delete');
        $event->getManager()->trigger('storage.image.delete');

        $model = new Model\ArrayModel();
        $model->setData([
            'imageIdentifier' => $event->getRequest()->getImageIdentifier(),
        ]);

        $event->getResponse()->setModel($model);
    }

    /**
     * Handle GET and HEAD requests
     *
     * @param EventInterface
     */
    public function getImage(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $eventManager = $event->getManager();

        $user = $request->getUser();
        $imageIdentifier = $request->getImageIdentifier();

        $image = new Model\Image();
        $image->setImageIdentifier($imageIdentifier)
              ->setUser($user);

        $response->setModel($image);

        // Load image details from database
        $eventManager->trigger('db.image.load');

        // Set a long max age as the image itself won't change
        $response->setMaxAge(31536000);

        // Custom Imbo headers, based on original
        $response->headers->add([
            'X-Imbo-OriginalMimeType' => $image->getMimeType(),
            'X-Imbo-OriginalWidth' => $image->getWidth(),
            'X-Imbo-OriginalHeight' => $image->getHeight(),
            'X-Imbo-OriginalFileSize' => $image->getFilesize(),
            'X-Imbo-OriginalExtension' => $image->getExtension(),
        ]);

        // Trigger loading of the image
        $eventManager->trigger('storage.image.load');

        // Trigger possible image transformations
        $eventManager->trigger('image.transform');
    }
}
