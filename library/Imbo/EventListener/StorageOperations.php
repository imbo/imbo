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
    Imbo\Exception\StorageException;

/**
 * Storage operations event listener
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Event\Listeners
 */
class StorageOperations implements ListenerInterface {
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'storage.image.delete' => 'deleteImage',
            'storage.image.load' => 'loadImage',
            'storage.image.insert' => 'insertImage',
        ];
    }

    /**
     * Delete an image
     *
     * @param EventInterface $event An event instance
     */
    public function deleteImage(EventInterface $event) {
        $request = $event->getRequest();
        $event->getStorage()->delete($request->getUser(), $request->getImageIdentifier());
    }

    /**
     * Load an image
     *
     * @param EventInterface $event An event instance
     */
    public function loadImage(EventInterface $event) {
        $storage = $event->getStorage();
        $request = $event->getRequest();
        $response = $event->getResponse();
        $user = $request->getUser();
        $imageIdentifier = $request->getImageIdentifier();

        $imageData = $storage->getImage($user, $imageIdentifier);
        $lastModified = $storage->getLastModified($user, $imageIdentifier);

        $response->setLastModified($lastModified)
                 ->getModel()->setBlob($imageData);

        $event->getManager()->trigger('image.loaded');
    }

    /**
     * Insert an image
     *
     * @param EventInterface $event An event instance
     */
    public function insertImage(EventInterface $event) {
        $request = $event->getRequest();
        $user = $request->getUser();
        $image = $request->getImage();
        $imageIdentifier = $image->getImageIdentifier();
        $blob = $image->getBlob();

        try {
            $exists = $event->getStorage()->imageExists($user, $imageIdentifier);
            $event->getStorage()->store(
                $user,
                $imageIdentifier,
                $blob
            );
        } catch (StorageException $e) {
            $event->getDatabase()->deleteImage(
                $user,
                $imageIdentifier
            );

            throw $e;
        }

        $event->getResponse()->setStatusCode($exists ? 200 : 201);
    }
}
