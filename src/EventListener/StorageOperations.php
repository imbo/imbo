<?php
namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface;
use Imbo\Exception\StorageException;

/**
 * Storage operations event listener
 *
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

        // Since the image is actually valid, this means that the backend
        // were unable to read the image for some reason. Might be NFS
        // that's down, web-backed storage being unavailable or something
        // similar.
        if ($imageData === false) {
            throw new StorageException('Failed reading file from storage backend for user ' . $user . ', id: ' . $imageIdentifier, 503);
        }

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
