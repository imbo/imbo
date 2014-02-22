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
        return array(
            'storage.image.delete' => 'deleteImage',
            'storage.image.load' => 'loadImage',
            'storage.image.insert' => 'insertImage',
        );
    }

    /**
     * Delete an image
     *
     * @param EventInterface $event An event instance
     */
    public function deleteImage(EventInterface $event) {
        $request = $event->getRequest();
        $event->getStorage()->delete($request->getPublicKey(), $request->getImageIdentifier());
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
        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();

        $imageData = $storage->getImage($publicKey, $imageIdentifier);
        $lastModified = $storage->getLastModified($publicKey, $imageIdentifier);

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
        $publicKey = $request->getPublicKey();
        $image = $request->getImage();
        $imageIdentifier = $image->getChecksum();
        $blob = $image->getBlob();

        try {
            $exists = $event->getStorage()->imageExists($publicKey, $imageIdentifier);
            $event->getStorage()->store(
                $publicKey,
                $imageIdentifier,
                $blob
            );
        } catch (StorageException $e) {
            $event->getDatabase()->deleteImage(
                $publicKey,
                $imageIdentifier
            );

            throw $e;
        }

        $event->getResponse()->setStatusCode($exists ? 200 : 201);
    }
}
