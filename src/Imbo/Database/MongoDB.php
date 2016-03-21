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
    MongoClient,
    MongoCollection,
    MongoException,
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
     * Mongo client instance
     *
     * @var MongoClient
     */
    private $mongoClient;

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
        'options' => ['connect' => true, 'connectTimeoutMS' => 1000],
    ];

    /**
     * Class constructor
     *
     * @param array $params Parameters for the driver
     * @param MongoClient $client MongoClient instance
     * @param MongoCollection $imageCollection MongoCollection instance for the images
     * @param MongoCollection $shortUrlCollection MongoCollection instance for the short URLs
     */
    public function __construct(array $params = null, MongoClient $client = null, MongoCollection $imageCollection = null, MongoCollection $shortUrlCollection = null) {
        if ($params !== null) {
            $this->params = array_replace_recursive($this->params, $params);
        }

        if ($client !== null) {
            $this->mongoClient = $client;
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
                $this->getImageCollection()->update(
                    ['user' => $user, 'imageIdentifier' => $imageIdentifier],
                    ['$set' => ['updated' => $now]],
                    ['multiple' => false]
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
            $this->getImageCollection()->insert($data);
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

            $this->getImageCollection()->remove(
                ['user' => $user, 'imageIdentifier' => $imageIdentifier],
                ['justOne' => true]
            );
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

            $this->getImageCollection()->update(
                ['user' => $user, 'imageIdentifier' => $imageIdentifier],
                ['$set' => ['updated' => time(), 'metadata' => $updatedMetadata]],
                ['multiple' => false]
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

        return isset($data['metadata']) ? $data['metadata'] : [];
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

            $this->getImageCollection()->update(
                ['user' => $user, 'imageIdentifier' => $imageIdentifier],
                ['$set' => ['metadata' => []]],
                ['multiple' => false]
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
        $fields = array_fill_keys([
            'extension', 'added', 'checksum', 'originalChecksum', 'updated',
            'user', 'imageIdentifier', 'mime', 'size', 'width', 'height'
        ], true);

        if ($query->returnMetadata()) {
            $fields['metadata'] = true;
        }

        try {
            $cursor = $this->getImageCollection()->find($queryData, $fields)
                                                 ->limit($query->limit())
                                                 ->sort($sort);

            // Skip some images if a page has been set
            if (($page = $query->page()) > 1) {
                $skip = $query->limit() * ($page - 1);
                $cursor->skip($skip);
            }

            foreach ($cursor as $image) {
                unset($image['_id']);
                $image['added'] = new DateTime('@' . $image['added'], new DateTimeZone('UTC'));
                $image['updated'] = new DateTime('@' . $image['updated'], new DateTimeZone('UTC'));
                $images[] = $image;
            }

            // Update model
            $model->setHits($cursor->count());
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to search for images', 500, $e);
        }

        return $images;
    }

    /**
     * {@inheritdoc}
     */
    public function getImageProperties($user, $imageIdentifier) {
        try {
            $data = $this->getImageCollection()->findOne(
                ['user' => $user, 'imageIdentifier' => $imageIdentifier],
                array_fill_keys(['size', 'width', 'height', 'mime', 'extension', 'added', 'updated'], true)
            );
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to fetch image data', 500, $e);
        }
        if ($data === null) {
            throw new DatabaseException('Image not found', 404);
        }
        return $data;
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

            // Create the cursor
            $cursor = $this->getImageCollection()->find($query, ['updated' => true])
                                                 ->limit(1)
                                                 ->sort([
                                                     'updated' => -1,
                                                 ]);

            // Fetch the next row
            $data = $cursor->getNext();
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to fetch image data', 500, $e);
        }

        if ($data === null && $imageIdentifier) {
            throw new DatabaseException('Image not found', 404);
        } else if ($data === null) {
            $data = ['updated' => time()];
        }

        return new DateTime('@' . $data['updated'], new DateTimeZone('UTC'));
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
                $results = $collection->aggregate(['$match' => ['user' => $user]], $group);
            } else {
                $results = $collection->aggregate($group);
            }

            $result = $results['result'];

            if (empty($result)) {
                return 0;
            }

            return (int) $result[0]['numBytes'];
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
            return $this->getMongoClient()->connect();
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

        return $data['mime'];
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

            $this->getShortUrlCollection()->insert($data);
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
                'shortUrlId' => true,
            ]);

            if (!$result) {
                return null;
            }

            return $result['shortUrlId'];
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
                '_id' => false
            ]);

            if (!$result) {
                return null;
            }

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
            $this->getShortUrlCollection()->remove($query);
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to delete short URLs', 500, $e);
        }

        return true;
    }

    /**
     * Fetch the image collection
     *
     * @return MongoCollection
     */
    private function getImageCollection() {
        return $this->getCollection('image');
    }

    /**
     * Fetch the shortUrl collection
     *
     * @return MongoCollection
     */
    private function getShortUrlCollection() {
        return $this->getCollection('shortUrl');
    }

    /**
     * Get the mongo collection instance
     *
     * @param string $type "image" or "shortUrl"
     * @return MongoCollection
     */
    private function getCollection($type) {
        if ($this->collections[$type] === null) {
            try {
                $this->collections[$type] = $this->getMongoClient()->selectCollection(
                    $this->params['databaseName'],
                    $type
                );
            } catch (MongoException $e) {
                throw new DatabaseException('Could not select collection', 500, $e);
            }
        }

        return $this->collections[$type];
    }

    /**
     * Get the mongo client instance
     *
     * @return MongoClient
     */
    private function getMongoClient() {
        if ($this->mongoClient === null) {
            try {
                $this->mongoClient = new MongoClient($this->params['server'], $this->params['options']);
            } catch (MongoException $e) {
                throw new DatabaseException('Could not connect to database', 500, $e);
            }
        }

        return $this->mongoClient;
    }
}
