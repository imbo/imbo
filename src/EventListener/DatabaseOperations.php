<?php declare(strict_types=1);

namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface;
use Imbo\Model;
use Imbo\Resource\Images\Query as ImagesQuery;

/**
 * Database operations event listener.
 */
class DatabaseOperations implements ListenerInterface
{
    /**
     * An images query object.
     */
    private ?ImagesQuery $imagesQuery = null;

    public static function getSubscribedEvents(): array
    {
        return [
            'db.image.insert' => 'insertImage',
            'db.image.delete' => 'deleteImage',
            'db.image.load' => 'loadImage',
            'db.images.load' => 'loadImages',
            'db.metadata.delete' => 'deleteMetadata',
            'db.metadata.update' => 'updateMetadata',
            'db.metadata.load' => 'loadMetadata',
            'db.user.load' => 'loadUser',
            'db.stats.load' => 'loadStats',
        ];
    }

    /**
     * Set the images query.
     *
     * @param ImagesQuery $query The query object
     */
    public function setImagesQuery(ImagesQuery $query): self
    {
        $this->imagesQuery = $query;

        return $this;
    }

    /**
     * Get the images query.
     */
    public function getImagesQuery(): ImagesQuery
    {
        if (!$this->imagesQuery) {
            $this->imagesQuery = new ImagesQuery();
        }

        return $this->imagesQuery;
    }

    /**
     * Insert an image.
     *
     * @param EventInterface $event  An event instance
     * @param array          $params Optional arguments to the insert method
     *                               - `updateIfDuplicate` controls whether an update will happen if the imageid already exists
     */
    public function insertImage(EventInterface $event, array $params = []): void
    {
        $request = $event->getRequest();

        $updateIfDuplicate = !isset($params['updateIfDuplicate']) || !empty($params['updateIfDuplicate']);

        $event->getDatabase()->insertImage(
            $request->getUser(),
            $request->getImage()->getImageIdentifier(),
            $request->getImage(),
            $updateIfDuplicate,
        );
    }

    /**
     * Delete an image.
     *
     * @param EventInterface $event An event instance
     */
    public function deleteImage(EventInterface $event): void
    {
        $request = $event->getRequest();

        $event->getDatabase()->deleteImage(
            $request->getUser(),
            $request->getImageIdentifier(),
        );
    }

    /**
     * Load an image.
     *
     * @param EventInterface $event An event instance
     */
    public function loadImage(EventInterface $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $event->getDatabase()->load(
            $request->getUser(),
            $request->getImageIdentifier(),
            $response->getModel(),
        );
    }

    /**
     * Delete metadata.
     *
     * @param EventInterface $event An event instance
     */
    public function deleteMetadata(EventInterface $event): void
    {
        $request = $event->getRequest();

        $event->getDatabase()->deleteMetadata(
            $request->getUser(),
            $request->getImageIdentifier(),
        );

        $event->getDatabase()->setLastModifiedNow(
            $request->getUser(),
            $request->getImageIdentifier(),
        );
    }

    /**
     * Update metadata.
     *
     * @param EventInterface $event An event instance
     */
    public function updateMetadata(EventInterface $event): void
    {
        $request = $event->getRequest();

        $event->getDatabase()->updateMetadata(
            $request->getUser(),
            $request->getImageIdentifier(),
            $event->getArgument('metadata'),
        );

        $event->getDatabase()->setLastModifiedNow(
            $request->getUser(),
            $request->getImageIdentifier(),
        );
    }

    /**
     * Load metadata.
     *
     * @param EventInterface $event An event instance
     */
    public function loadMetadata(EventInterface $event): void
    {
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
     * Load images.
     *
     * @param EventInterface $event An event instance
     */
    public function loadImages(EventInterface $event): void
    {
        $query = $this->getImagesQuery();
        $params = $event->getRequest()->query;
        $returnMetadata = false;

        if ($params->has('page')) {
            $query->setPage((int) $params->get('page'));
        }

        if ($params->has('limit')) {
            $query->setLimit((int) $params->get('limit'));
        }

        if ($params->has('metadata')) {
            $query->setReturnMetadata(true);
            $returnMetadata = true;
        }

        if ($params->has('from')) {
            $query->setFrom((int) $params->get('from'));
        }

        if ($params->has('to')) {
            $query->setTo((int) $params->get('to'));
        }

        if ($params->has('sort')) {
            $sort = $params->all('sort');
            $query->setSort($sort);
        }

        if ($params->has('ids')) {
            $ids = $params->all('ids');
            $query->setImageIdentifiers($ids);
        }

        if ($params->has('checksums')) {
            $checksums = $params->all('checksums');
            $query->setChecksums($checksums);
        }

        if ($params->has('originalChecksums')) {
            $checksums = $params->all('originalChecksums');
            $query->setOriginalChecksums($checksums);
        }

        if ($event->hasArgument('users')) {
            $users = $event->getArgument('users');
        } else {
            $users = $event->getRequest()->getUsers();
        }

        $response = $event->getResponse();
        $database = $event->getDatabase();

        // Create the model and set some pagination values
        $model = new Model\Images();
        $model->setLimit($query->getLimit())
              ->setPage($query->getPage());

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
            $fields = $params->all('fields');
            $model->setFields($fields);
        }

        $lastModified = $database->getLastModified($users);

        $response->setModel($model)
                 ->setLastModified($lastModified);
    }

    /**
     * Load user data.
     *
     * @param EventInterface $event An event instance
     */
    public function loadUser(EventInterface $event): void
    {
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
     * Load stats.
     *
     * @param EventInterface $event An event instance
     */
    public function loadStats(EventInterface $event): void
    {
        $response = $event->getResponse();
        $database = $event->getDatabase();

        $statsModel = new Model\Stats();
        $statsModel->setNumUsers($database->getNumUsers());
        $statsModel->setNumBytes($database->getNumBytes());
        $statsModel->setNumImages($database->getNumImages());

        $response->setModel($statsModel);
    }
}
