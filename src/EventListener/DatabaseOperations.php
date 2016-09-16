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
    Imbo\Resource\Images\Query as ImagesQuery,
    Imbo\Model;

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
        return [
            'db.image.insert'    => 'insertImage',
            'db.image.delete'    => 'deleteImage',
            'db.image.load'      => 'loadImage',
            'db.images.load'     => 'loadImages',
            'db.metadata.delete' => 'deleteMetadata',
            'db.metadata.update' => 'updateMetadata',
            'db.metadata.load'   => 'loadMetadata',
            'db.user.load'       => 'loadUser',
            'db.stats.load'      => 'loadStats',
        ];
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
            $request->getUser(),
            $request->getImage()->getImageIdentifier(),
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
            $request->getUser(),
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
            $request->getUser(),
            $request->getImageIdentifier(),
            $response->getModel()
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
            $request->getUser(),
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
            $request->getUser(),
            $request->getImageIdentifier(),
            $event->getArgument('metadata')
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
        $user = $request->getUser();
        $imageIdentifier = $request->getImageIdentifier();
        $database = $event->getDatabase();

        $model = new Model\Metadata();
        $model->setData($database->getMetadata($user, $imageIdentifier));

        $response->setModel($model)
                 ->setLastModified($database->getLastModified([$user], $imageIdentifier));
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
            $sort = $params->get('sort');

            if (is_array($sort)) {
                $query->sort($sort);
            }
        }

        if ($params->has('ids')) {
            $ids = $params->get('ids');

            if (is_array($ids)) {
                $query->imageIdentifiers($ids);
            }
        }

        if ($params->has('checksums')) {
            $checksums = $params->get('checksums');

            if (is_array($checksums)) {
                $query->checksums($checksums);
            }
        }

        if ($params->has('originalChecksums')) {
            $checksums = $params->get('originalChecksums');

            if (is_array($checksums)) {
                $query->originalChecksums($checksums);
            }
        }

        if ($event->hasArgument('users')) {
            $users = $event->getArgument('users');
        } else {
            $users = $event->getRequest()->getUsers();

            if (!is_array($users)) {
                $users = [];
            }
        }

        $response = $event->getResponse();
        $database = $event->getDatabase();

        // Create the model and set some pagination values
        $model = new Model\Images();
        $model->setLimit($query->limit())
              ->setPage($query->page());

        $images = $database->getImages($users, $query, $model);
        $modelImages = [];

        foreach ($images as $image) {
            $entry = new Model\Image();
            $entry->setFilesize($image['size'])
                  ->setWidth($image['width'])
                  ->setHeight($image['height'])
                  ->setUser($image['user'])
                  ->setImageIdentifier($image['imageIdentifier'])
                  ->setChecksum($image['checksum'])
                  ->setOriginalChecksum(isset($image['originalChecksum']) ? $image['originalChecksum'] : null)
                  ->setMimeType($image['mime'])
                  ->setExtension($image['extension'])
                  ->setAddedDate($image['added'])
                  ->setUpdatedDate($image['updated']);

            if ($returnMetadata) {
                $entry->setMetadata($image['metadata']);
            }

            $modelImages[] = $entry;
        }

        // Add images to the model
        $model->setImages($modelImages);

        if ($params->has('fields')) {
            $fields = $params->get('fields');

            if (is_array($fields)) {
                $model->setFields($fields);
            }
        }

        $lastModified = $database->getLastModified($users);

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
        $user = $request->getUser();
        $database = $event->getDatabase();

        $numImages = $database->getNumImages($user);
        $lastModified = $database->getLastModified([$user]);

        $userModel = new Model\User();
        $userModel->setUserId($user)
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

        $statsModel = new Model\Stats();
        $statsModel->setNumUsers($database->getNumUsers());
        $statsModel->setNumBytes($database->getNumBytes());
        $statsModel->setNumImages($database->getNumImages());

        $response->setModel($statsModel);
    }
}
