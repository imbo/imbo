<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Database;

use Imbo\Model\Image,
    Imbo\Model\Images,
    Imbo\Resource\Images\Query,
    Imbo\Exception\DatabaseException,
    Imbo\Helpers\ObjectToArray,
    MongoDB\Driver\Command,
    MongoDB\Driver\Manager as DriverManager,
    MongoDB\Collection,
    MongoDB\Driver\Exception\Exception as MongoException,
    DateTime,
    DateTimeZone;

/**
 * MongoDB database driver
 *
 * A MongoDB database driver for Imbo
 *
 * Valid parameters for this driver:
 *
 * - (string) databaseName Name of the database. Defaults to 'imbo'
 * - (string) server The server string to use when connecting to MongoDB. Defaults to
 *                   'mongodb://localhost:27017'
 * - (array) options Options to use when creating the MongoClient instance. Defaults to
 *                   ['connect' => true, 'connectTimeoutMS' => 1000].
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Database
 */
class MongoDB implements DatabaseInterface {
    /**
     * MongoDB driver manager instance
     *
     * @var MongoDB\Driver\Manager
     */
    private $driverManager;

    /**
     * The collection instances used by the driver
     *
     * @var array
     */
    private $collections = [
        'image' => null,
        'shortUrl' => null,
    ];

    /**
     * Parameters for the driver
     *
     * @var array
     */
    private $params = [
        // Database name
        'databaseName' => 'imbo',

        // Server string and ctor options
        'server'  => 'mongodb://localhost:27017',
        'options' => ['connectTimeoutMS' => 1000],
    ];

    /**
     * Class constructor
     *
     * @param array $params Parameters for the driver
     * @param MongoDB\Driver\Manager $manager Driver manager instance
     * @param MongoDB\Collection $imageCollection Collection instance for the images
     * @param MongoDB\Collection $shortUrlCollection Collection instance for short URLs
     */
    public function __construct(array $params = null, DriverManager $manager = null, Collection $imageCollection = null, Collection $shortUrlCollection = null) {
        if ($params !== null) {
            $this->params = array_replace_recursive($this->params, $params);
        }

        if ($manager !== null) {
            $this->driverManager = $manager;
        }

        if ($imageCollection !== null) {
            $this->collections['image'] = $imageCollection;
        }

        if ($shortUrlCollection !== null) {
            $this->collections['shortUrl'] = $shortUrlCollection;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function insertImage($user, $imageIdentifier, Image $image) {
        $now = time();

        if ($added = $image->getAddedDate()) {
            $added = $added->getTimestamp();
        }

        if ($updated = $image->getUpdatedDate()) {
            $updated = $updated->getTimestamp();
        }

        if ($this->imageExists($user, $imageIdentifier)) {
            try {
                $this->getImageCollection()->updateOne(
                    ['user' => $user, 'imageIdentifier' => $imageIdentifier],
                    ['$set' => ['updated' => $now]]
                );

                return true;
            } catch (MongoException $e) {
                throw new DatabaseException('Unable to save image data', 500, $e);
            }
        }

        $data = [
            'size'             => $image->getFilesize(),
            'user'             => $user,
            'imageIdentifier'  => $imageIdentifier,
            'extension'        => $image->getExtension(),
            'mime'             => $image->getMimeType(),
            'metadata'         => [],
            'added'            => $added ?: $now,
            'updated'          => $updated ?: $now,
            'width'            => $image->getWidth(),
            'height'           => $image->getHeight(),
            'checksum'         => $image->getChecksum(),
            'originalChecksum' => $image->getOriginalChecksum(),
        ];

        try {
            $this->getImageCollection()->insertOne($data);
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to save image data', 500, $e);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteImage($user, $imageIdentifier) {
        try {
            $data = $this->getImageCollection()->findOne([
                'user' => $user,
                'imageIdentifier' => $imageIdentifier,
            ]);

            if ($data === null) {
                throw new DatabaseException('Image not found', 404);
            }

            $this->getImageCollection()->deleteOne([
                'user' => $user,
                'imageIdentifier' => $imageIdentifier
            ]);
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to delete image data', 500, $e);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function updateMetadata($user, $imageIdentifier, array $metadata) {
        try {
            // Fetch existing metadata and merge with the incoming data
            $existing = $this->getMetadata($user, $imageIdentifier);
            $updatedMetadata = array_merge($existing, $metadata);

            $this->getImageCollection()->updateOne(
                ['user' => $user, 'imageIdentifier' => $imageIdentifier],
                ['$set' => ['updated' => time(), 'metadata' => $updatedMetadata]]
            );
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to update meta data', 500, $e);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($user, $imageIdentifier) {
        try {
            $data = $this->getImageCollection()->findOne([
                'user' => $user,
                'imageIdentifier' => $imageIdentifier,
            ]);
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to fetch meta data', 500, $e);
        }

        if ($data === null) {
            throw new DatabaseException('Image not found', 404);
        }

        return isset($data->metadata) ? ObjectToArray::toArray($data->metadata) : [];
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMetadata($user, $imageIdentifier) {
        try {
            $data = $this->getImageCollection()->findOne([
                'user' => $user,
                'imageIdentifier' => $imageIdentifier,
            ]);

            if ($data === null) {
                throw new DatabaseException('Image not found', 404);
            }

            $this->getImageCollection()->updateOne(
                ['user' => $user, 'imageIdentifier' => $imageIdentifier],
                ['$set' => ['metadata' => []]]
            );
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to delete meta data', 500, $e);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getImages(array $users, Query $query, Images $model) {
        // Initialize return value
        $images = [];

        // Query data
        $queryData = ['user' => ['$in' => $users]];

        $from = $query->from();
        $to = $query->to();

        if ($from || $to) {
            $tmp = [];

            if ($from !== null) {
                $tmp['$gte'] = $from;
            }

            if ($to !== null) {
                $tmp['$lte'] = $to;
            }

            $queryData['added'] = $tmp;
        }

        $imageIdentifiers = $query->imageIdentifiers();

        if (!empty($imageIdentifiers)) {
            $queryData['imageIdentifier']['$in'] = $imageIdentifiers;
        }

        $checksums = $query->checksums();

        if (!empty($checksums)) {
            $queryData['checksum']['$in'] = $checksums;
        }

        $originalChecksums = $query->originalChecksums();

        if (!empty($originalChecksums)) {
            $queryData['originalChecksum']['$in'] = $originalChecksums;
        }

        // Sorting
        $sort = ['added' => -1];

        if ($querySort = $query->sort()) {
            $sort = [];

            foreach ($querySort as $s) {
                $sort[$s['field']] = ($s['sort'] === 'asc' ? 1 : -1);
            }
        }

        // Fields to fetch
        $projection = array_fill_keys([
            'extension', 'added', 'checksum', 'originalChecksum', 'updated',
            'user', 'imageIdentifier', 'mime', 'size', 'width', 'height'
        ], true);

        if ($query->returnMetadata()) {
            $projection['metadata'] = true;
        }

        try {
            $options = [
                'projection' => $projection,
                'limit' => $query->limit(),
                'sort' => $sort,
            ];

            // Skip some images if a page has been set
            if (($page = $query->page()) > 1) {
                $skip = $query->limit() * ($page - 1);
                $options['skip'] = $skip;
            }

            $imageCollection = $this->getImageCollection();
            $cursor = $imageCollection->find($queryData, $options);

            foreach ($cursor as $image) {
                $image = ObjectToArray::toArray($image);

                unset($image['_id']);
                $image['added'] = new DateTime('@' . $image['added'], new DateTimeZone('UTC'));
                $image['updated'] = new DateTime('@' . $image['updated'], new DateTimeZone('UTC'));
                $images[] = $image;
            }

            // Update model
            $model->setHits($imageCollection->count($queryData));
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to search for images', 500, $e);
        }

        return $images;
    }

    /**
     * {@inheritdoc}
     */
    public function getImageProperties($user, $imageIdentifier) {
        $projection = array_fill_keys([
            'size', 'width', 'height', 'mime', 'extension', 'added', 'updated'
        ], true);

        try {
            $data = $this->getImageCollection()->findOne(
                ['user' => $user, 'imageIdentifier' => $imageIdentifier],
                ['projection' => $projection]
            );
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to fetch image data', 500, $e);
        }

        if ($data === null) {
            throw new DatabaseException('Image not found', 404);
        }

        return ObjectToArray::toArray($data);
    }

    /**
     * {@inheritdoc}
     */
    public function load($user, $imageIdentifier, Image $image) {
        $data = $this->getImageProperties($user, $imageIdentifier);

        $image->setWidth($data['width'])
              ->setHeight($data['height'])
              ->setFilesize($data['size'])
              ->setMimeType($data['mime'])
              ->setExtension($data['extension'])
              ->setAddedDate(new DateTime('@' . $data['added'], new DateTimeZone('UTC')))
              ->setUpdatedDate(new DateTime('@' . $data['updated'], new DateTimeZone('UTC')));

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastModified(array $users, $imageIdentifier = null) {
        try {
            // Query on the user
            $query = ['user' => ['$in' => $users]];

            if ($imageIdentifier) {
                // We want information about a single image. Add the identifier to the query
                $query['imageIdentifier'] = $imageIdentifier;
            }

            $data = $this->getImageCollection()->findOne($query, [
                'sort' => ['updated' => -1],
                'projection' => ['updated' => true]
            ]);
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to fetch image data', 500, $e);
        }

        if ($data === null && $imageIdentifier) {
            throw new DatabaseException('Image not found', 404);
        } else if ($data === null) {
            return new DateTime('now', new DateTimeZone('UTC'));
        }

        return new DateTime('@' . $data->updated, new DateTimeZone('UTC'));
    }

    /**
     * {@inheritdoc}
     */
    public function getNumImages($user = null) {
        try {
            $query = [];

            if ($user) {
                $query['user'] = $user;
            }

            $result = (int) $this->getImageCollection()->count($query);

            return $result;
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to fetch information from the database', 500, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNumBytes($user = null) {
        try {
            $collection = $this->getImageCollection();
            $group = ['$group' => ['_id' => null, 'numBytes' => ['$sum' => '$size']]];

            if ($user) {
                $pipeline = [['$match' => ['user' => $user]]];
                $pipeline[] = $group;

                $results = $collection->aggregate($pipeline);
            } else {
                $results = $collection->aggregate([$group]);
            }

            $result = $results->current();

            if (empty($result)) {
                return 0;
            }

            return $result->numBytes;
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to fetch information from the database', 500, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNumUsers() {
        try {
            $result = (int) count($this->getImageCollection()->distinct('user'));

            return $result;
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to fetch information from the database', 500, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus() {
        try {
            $manager = $this->getDriverManager();

            // Mongo connects lazily, so we might have to ping it to get the actual status
            $isConnected = (bool) $manager->getServers();
            if ($isConnected) {
                return true;
            }

            $command = new Command(['ping' => 1]);
            $manager->executeCommand('db', $command);

            return (bool) $manager->getServers();
        } catch (DatabaseException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getImageMimeType($user, $imageIdentifier) {
        try {
            $data = $this->getImageCollection()->findOne([
                'user' => $user,
                'imageIdentifier' => $imageIdentifier,
            ]);
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to fetch image meta data', 500, $e);
        }

        if ($data === null) {
            throw new DatabaseException('Image not found', 404);
        }

        return $data->mime;
    }

    /**
     * {@inheritdoc}
     */
    public function imageExists($user, $imageIdentifier) {
        $data = $this->getImageCollection()->findOne([
            'user' => $user,
            'imageIdentifier' => $imageIdentifier,
        ]);

        return $data !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function insertShortUrl($shortUrlId, $user, $imageIdentifier, $extension = null, array $query = []) {
        try {
            $data = [
                'shortUrlId' => $shortUrlId,
                'user' => $user,
                'imageIdentifier' => $imageIdentifier,
                'extension' => $extension,
                'query' => serialize($query),
            ];

            $this->getShortUrlCollection()->insertOne($data);
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to create short URL', 500, $e);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getShortUrlId($user, $imageIdentifier, $extension = null, array $query = []) {
        try {
            $result = $this->getShortUrlCollection()->findOne([
                'user' => $user,
                'imageIdentifier' => $imageIdentifier,
                'extension' => $extension,
                'query' => serialize($query),
            ], [
                'projection' => ['shortUrlId' => true],
            ]);

            if (!$result) {
                return null;
            }

            return $result->shortUrlId;
        } catch (MongoException $e) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getShortUrlParams($shortUrlId) {
        try {
            $result = $this->getShortUrlCollection()->findOne([
                'shortUrlId' => $shortUrlId,
            ], [
                'projection' => ['_id' => false]
            ]);

            if (!$result) {
                return null;
            }

            $result = ObjectToArray::toArray($result);
            $result['query'] = unserialize($result['query']);

            return $result;
        } catch (MongoException $e) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteShortUrls($user, $imageIdentifier, $shortUrlId = null) {
        $query = [
            'user' => $user,
            'imageIdentifier' => $imageIdentifier,
        ];

        if ($shortUrlId) {
            $query['shortUrlId'] = $shortUrlId;
        }

        try {
            $this->getShortUrlCollection()->deleteMany($query);
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to delete short URLs', 500, $e);
        }

        return true;
    }

    /**
     * Fetch the image collection
     *
     * @return MongoDB\Collection
     */
    private function getImageCollection() {
        return $this->getCollection('image');
    }

    /**
     * Fetch the shortUrl collection
     *
     * @return MongoDB\Collection
     */
    private function getShortUrlCollection() {
        return $this->getCollection('shortUrl');
    }

    /**
     * Get the mongo collection instance
     *
     * @param string $type "image" or "shortUrl"
     * @return MongoDB\Collection
     */
    private function getCollection($type) {
        if ($this->collections[$type] === null) {
            try {
                $this->collections[$type] = new Collection(
                    $this->getDriverManager(),
                    $this->getCollectionNamespace($type)
                );
            } catch (MongoException $e) {
                throw new DatabaseException('Could not select collection', 500, $e);
            }
        }

        return $this->collections[$type];
    }

    /**
     * Get the namespaced collection name for a given collection
     *
     * @param string $name Name of collection
     * @return string
     */
    private function getCollectionNamespace($name) {
        return $this->params['databaseName'] . '.' . $name;
    }

    /**
     * Get the mongo driver manager instance
     *
     * @return MongoDB\Driver\Manager
     */
    private function getDriverManager() {
        if ($this->driverManager === null) {
            try {
                $this->driverManager = new DriverManager(
                    $this->params['server'],
                    $this->params['options']
                );
            } catch (MongoException $e) {
                throw new DatabaseException('Could not connect to database', 500, $e);
            }
        }

        return $this->driverManager;
    }
}
