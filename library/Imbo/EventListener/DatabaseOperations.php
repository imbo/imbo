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
    Imbo\Database\DatabaseInterface,
    Imbo\Resource\Images\Query as ImagesQuery,
    Imbo\Model,
    DateTime;

/**
 * Database operations event listener
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Event\Listeners
 */
class DatabaseOperations implements ListenerInterface {
    /**
     * An images query object
     *
     * @var ImagesQuery
     */
    private $imagesQuery;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return array(
            'db.image.insert'    => 'insertImage',
            'db.image.delete'    => 'deleteImage',
            'db.image.load'      => 'loadImage',
            'db.images.load'     => 'loadImages',
            'db.metadata.delete' => 'deleteMetadata',
            'db.metadata.update' => 'updateMetadata',
            'db.metadata.load'   => 'loadMetadata',
            'db.user.load'       => 'loadUser',
            'db.stats.load'      => 'loadStats',
        );
    }

    /**
     * Set the images query
     *
     * @param ImagesQuery $query The query object
     * @return self
     */
    public function setImagesQuery(ImagesQuery $query) {
        $this->imagesQuery = $query;

        return $this;
    }

    /**
     * Get the images query
     *
     * @return ImagesQuery
     */
    public function getImagesQuery() {
        if (!$this->imagesQuery) {
            $this->imagesQuery = new ImagesQuery();
        }

        return $this->imagesQuery;
    }

    /**
     * Insert an image
     *
     * @param EventInterface $event An event instance
     */
    public function insertImage(EventInterface $event) {
        $request = $event->getRequest();

        $event->getDatabase()->insertImage(
            $request->getPublicKey(),
            $request->getImage()->getChecksum(),
            $request->getImage()
        );
    }

    /**
     * Delete an image
     *
     * @param EventInterface $event An event instance
     */
    public function deleteImage(EventInterface $event) {
        $request = $event->getRequest();

        $event->getDatabase()->deleteImage(
            $request->getPublicKey(),
            $request->getImageIdentifier()
        );
    }

    /**
     * Load an image
     *
     * @param EventInterface $event An event instance
     */
    public function loadImage(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $event->getDatabase()->load(
            $request->getPublicKey(),
            $request->getImageIdentifier(),
            $response->getImage()
        );
    }

    /**
     * Delete metadata
     *
     * @param EventInterface $event An event instance
     */
    public function deleteMetadata(EventInterface $event) {
        $request = $event->getRequest();

        $event->getDatabase()->deleteMetadata(
            $request->getPublicKey(),
            $request->getImageIdentifier()
        );
    }

    /**
     * Update metadata
     *
     * @param EventInterface $event An event instance
     */
    public function updateMetadata(EventInterface $event) {
        $request = $event->getRequest();

        $event->getDatabase()->updateMetadata(
            $request->getPublicKey(),
            $request->getImageIdentifier(),
            json_decode($request->getContent(), true)
        );
    }

    /**
     * Load metadata
     *
     * @param EventInterface $event An event instance
     */
    public function loadMetadata(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();
        $database = $event->getDatabase();

        $model = new Model\Metadata();
        $model->setData($database->getMetadata($publicKey, $imageIdentifier));

        $response->setModel($model)
                 ->setLastModified($database->getLastModified($publicKey, $imageIdentifier));
    }

    /**
     * Load images
     *
     * @param EventInterface $event An event instance
     */
    public function loadImages(EventInterface $event) {
        $query = $this->getImagesQuery();
        $params = $event->getRequest()->query;
        $returnMetadata = false;

        if ($params->has('page')) {
            $query->page($params->get('page'));
        }

        if ($params->has('limit')) {
            $query->limit($params->get('limit'));
        }

        if ($params->has('metadata')) {
            $query->returnMetadata($params->get('metadata'));
            $returnMetadata = true;
        }

        if ($params->has('from')) {
            $query->from($params->get('from'));
        }

        if ($params->has('to')) {
            $query->to($params->get('to'));
        }

        if ($params->has('sort')) {
            $query->sort($params->get('sort'));
        }

        if ($params->has('query')) {
            $data = json_decode($params->get('query'), true);

            if (is_array($data)) {
                $query->metadataQuery($data);
            }
        }

        if ($params->has('imageIdentifiers')) {
            $imageIdentifiers = trim($params->get('imageIdentifiers'));

            if (!empty($imageIdentifiers)) {
                $query->imageIdentifiers(explode(',', $imageIdentifiers));
            }
        }

        $publicKey = $event->getRequest()->getPublicKey();
        $response = $event->getResponse();
        $database = $event->getDatabase();

        $images = $database->getImages($publicKey, $query);
        $modelImages = array();

        foreach ($images as $image) {
            $entry = new Model\Image();
            $entry->setFilesize($image['size'])
                  ->setWidth($image['width'])
                  ->setHeight($image['height'])
                  ->setPublicKey($publicKey)
                  ->setImageIdentifier($image['imageIdentifier'])
                  ->setChecksum($image['checksum'])
                  ->setMimeType($image['mime'])
                  ->setExtension($image['extension'])
                  ->setAddedDate($image['added'])
                  ->setUpdatedDate($image['updated']);

            if ($returnMetadata) {
                $entry->setMetadata($image['metadata']);
            }

            $modelImages[] = $entry;
        }

        $model = new Model\Images();
        $model->setImages($modelImages);

        if ($params->has('fields')) {
            $fields = trim($params->get('fields'));

            if (!empty($fields)) {
                $model->setFields(explode(',', $fields));
            }
        }

        $lastModified = $database->getLastModified($publicKey);

        $response->setModel($model)
                 ->setLastModified($lastModified);
    }

    /**
     * Load user data
     *
     * @param EventInterface $event An event instance
     */
    public function loadUser(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $publicKey = $request->getPublicKey();
        $database = $event->getDatabase();

        $numImages = $database->getNumImages($publicKey);
        $lastModified = $database->getLastModified($publicKey);

        $userModel = new Model\User();
        $userModel->setPublicKey($publicKey)
                  ->setNumImages($numImages)
                  ->setLastModified($lastModified);

        $response->setModel($userModel)
                 ->setLastModified($lastModified);
    }

    /**
     * Load stats
     *
     * @param EventInterface $event An event instance
     */
    public function loadStats(EventInterface $event) {
        $response = $event->getResponse();
        $database = $event->getDatabase();
        $publicKeys = array_keys($event->getConfig()['auth']);
        $users = array();

        foreach ($publicKeys as $key) {
            $users[$key] = array(
                'numImages' => $database->getNumImages($key),
                'numBytes' => $database->getNumBytes($key),
            );
        }

        $statsModel = new Model\Stats();
        $statsModel->setUsers($users);

        $response->setModel($statsModel);
    }
}
