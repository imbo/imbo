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
    MongoDB\Collection,
    MongoDB\Client,
    MongoDB\Driver\Exception\InvalidArgumentException,
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
class Mongo implements DatabaseInterface {
    /**
     * Mongo client instance
     *
     * @var Client
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
        'options' => [
            'connectTimeoutMS' => 1000
        ],
    ];

    /**
     * Class constructor
     *
     * @param array $params Parameters for the driver
     * @param Client $client Client instance
     * @param Collection $imageCollection Collection instance for the images
     * @param Collection $shortUrlCollection Collection instance for the short URLs
     */
    public function __construct(array $params = null, Client $client = null, Collection $imageCollection = null, Collection $shortUrlCollection = null) {
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
                $this->getImageCollection()->updateOne(
                    ['user' => $user, 'imageIdentifier' => $imageIdentifier],
                    ['$set' => ['updated' => $now]],
                    ['multiple' => false]
                );

                return true;
            } catch (InvalidArgumentException $e) {
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
        } catch (InvalidArgumentException $e) {
            throw new DatabaseException('Unable to save image data', 500, $e);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteImage($user, $imageIdentifier) {
        try {
            $data = $this->getImageCollection()->findOneAndDelete([
                'user' => $user,
                'imageIdentifier' => $imageIdentifier,
            ]);

            if ($data === null) {
                throw new DatabaseException('Image not found', 404);
            }
        } catch (InvalidArgumentException $e) {
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
                ['$set' => ['updated' => time(), 'metadata' => $updatedMetadata]],
                ['multiple' => false]
            );
        } catch (InvalidArgumentException $e) {
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
        } catch (InvalidArgumentException $e) {
            throw new DatabaseException('Unable to fetch meta data', 500, $e);
        }

        if ($data === null) {
            throw new DatabaseException('Image not found', 404);
        }

        return isset($data['metadata']) ? (array) $data['metadata'] : [];
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
                ['$set' => ['metadata' => []]],
                ['multiple' => false]
            );
        } catch (InvalidArgumentException $e) {
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

        $fields['limit'] = $query->limit();
        $fields['sort'] = $sort;

        // Skip some images if a page has been set
        if (($page = $query->page()) > 1) {
            $skip = $query->limit() * ($page - 1);
            $fields['skip'] = $skip;
        }

        try {
            $cursor = $this->getImageCollection()->find($queryData, $fields);

            foreach ($cursor as $image) {
                unset($image['_id']);
                $image['added'] = new DateTime('@' . $image['added'], new DateTimeZone('UTC'));
                $image['updated'] = new DateTime('@' . $image['updated'], new DateTimeZone('UTC'));
                $image['metadata'] = (array) $image['metadata'];
                $images[] = $image;
            }

            // $cursor->count is not available anymore
            // need to do a new query for the count based on the query data
            $count = $this->getImageCollection()->count($queryData);

            $model->setHits($count);
        } catch (InvalidArgumentException $e) {
            throw new DatabaseException('Unable to search for images', 500, $e);
        }

        return (array) $images;
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
        } catch (InvalidArgumentException $e) {
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
            $data = $this->getImageCollection()->findOne($query, [
                'updated' => true,
                'sort' => ['updated' => -1]
            ]);
        } catch (InvalidArgumentException $e) {
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
        } catch (InvalidArgumentException $e) {
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
                $results = $collection->aggregate([
                    ['$match' => ['user' => $user]],
                    $group
                ]);
            } else {
                $results = $collection->aggregate([$group]);
            }

            $data = [];

            foreach ($results as $result) {
                $data[] = $result;
            }

            if (empty($data)) {
                return 0;
            }

            return $data[0]['numBytes'];
        } catch (InvalidArgumentException $e) {
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
        } catch (InvalidArgumentException $e) {
            throw new DatabaseException('Unable to fetch information from the database', 500, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus() {
        try {
            $this->getMongoClient();

            return true;
        } catch(DatabaseException $e) {
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
        } catch (InvalidArgumentException $e) {
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

            $this->getShortUrlCollection()->insertOne($data);
        } catch (InvalidArgumentException $e) {
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
        } catch (InvalidArgumentException $e) {
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
        } catch (InvalidArgumentException $e) {
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
            $this->getShortUrlCollection()->deleteOne($query);
        } catch (InvalidArgumentException $e) {
            throw new DatabaseException('Unable to delete short URLs', 500, $e);
        }

        return true;
    }

    /**
     * Fetch the image collection
     *
     * @return Collection
     */
    private function getImageCollection() {
        return $this->getCollection('image');
    }

    /**
     * Fetch the shortUrl collection
     *
     * @return Collection
     */
    private function getShortUrlCollection() {
        return $this->getCollection('shortUrl');
    }

    /**
     * Get the mongo collection instance
     *
     * @param string $type "image" or "shortUrl"
     * @return Collection
     */
    private function getCollection($type) {
        if ($this->collections[$type] === null) {
            try {
                $this->collections[$type] = $this->getMongoClient()->selectCollection(
                    $this->params['databaseName'],
                    $type
                );
            } catch (InvalidArgumentException $e) {
                throw new DatabaseException('Could not select collection', 500, $e);
            }
        }

        return $this->collections[$type];
    }

    /**
     * Get the mongo client instance
     *
     * @return Client
     */
    private function getMongoClient() {
        if ($this->mongoClient === null) {
            try {
                $this->mongoClient = new Client($this->params['server'], $this->params['options']);
            } catch (InvalidArgumentException $e) {
                throw new DatabaseException('Could not connect to database', 500, $e);
            }
        }

        return $this->mongoClient;
    }
}
