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
    MongoRegex,
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
 *                   array('connect' => true, 'connectTimeoutMS' => 1000).
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
    private $collections = array(
        'image' => null,
        'shortUrl' => null,
    );

    /**
     * Parameters for the driver
     *
     * @var array
     */
    private $params = array(
        // Database name
        'databaseName' => 'imbo',

        // Server string and ctor options
        'server'  => 'mongodb://localhost:27017',
        'options' => array('connect' => true, 'connectTimeoutMS' => 1000),
    );

    /**
     * The string that will replace wildcards (*) in a metadata query $wildcard search, only to be
     * replaced with .* after quoting
     *
     * @var string
     */
    protected $regexWildcardReplacement = 'WILDCARDREPLACEMENT';

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
    public function insertImage($publicKey, $imageIdentifier, Image $image) {
        $now = time();

        if ($added = $image->getAddedDate()) {
            $added = $added->getTimestamp();
        }

        if ($updated = $image->getUpdatedDate()) {
            $updated = $updated->getTimestamp();
        }

        if ($this->imageExists($publicKey, $imageIdentifier)) {
            try {
                $this->getImageCollection()->update(
                    array('publicKey' => $publicKey, 'imageIdentifier' => $imageIdentifier),
                    array('$set' => array('updated' => $now)),
                    array('multiple' => false)
                );

                return true;
            } catch (MongoException $e) {
                throw new DatabaseException('Unable to save image data', 500, $e);
            }
        }

        $data = array(
            'size'             => $image->getFilesize(),
            'publicKey'        => $publicKey,
            'imageIdentifier'  => $imageIdentifier,
            'extension'        => $image->getExtension(),
            'mime'             => $image->getMimeType(),
            'metadata'         => array(),
            'metadata_n'       => array(),
            'added'            => $added ?: $now,
            'updated'          => $updated ?: $now,
            'width'            => $image->getWidth(),
            'height'           => $image->getHeight(),
            'checksum'         => $image->getChecksum(),
            'originalChecksum' => $image->getOriginalChecksum(),
        );

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
    public function deleteImage($publicKey, $imageIdentifier) {
        try {
            $data = $this->getImageCollection()->findOne(array(
                'publicKey' => $publicKey,
                'imageIdentifier' => $imageIdentifier,
            ));

            if ($data === null) {
                throw new DatabaseException('Image not found', 404);
            }

            $this->getImageCollection()->remove(
                array('publicKey' => $publicKey, 'imageIdentifier' => $imageIdentifier),
                array('justOne' => true)
            );
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to delete image data', 500, $e);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function updateMetadata($publicKey, $imageIdentifier, array $metadata) {
        try {
            // Fetch existing metadata and merge with the incoming data
            $existing = $this->getMetadata($publicKey, $imageIdentifier);
            $updatedMetadata = array_merge($existing, $metadata);

            $this->getImageCollection()->update(
                array('publicKey' => $publicKey, 'imageIdentifier' => $imageIdentifier),
                array('$set' => array(
                    'updated' => time(),
                    'metadata' => $updatedMetadata,
                    'metadata_n' => $this->lowercaseArray($updatedMetadata),
                )),
                array('multiple' => false)
            );
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to update meta data', 500, $e);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($publicKey, $imageIdentifier) {
        try {
            $data = $this->getImageCollection()->findOne(array(
                'publicKey' => $publicKey,
                'imageIdentifier' => $imageIdentifier,
            ));
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to fetch meta data', 500, $e);
        }

        if ($data === null) {
            throw new DatabaseException('Image not found', 404);
        }

        return isset($data['metadata']) ? $data['metadata'] : array();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMetadata($publicKey, $imageIdentifier) {
        try {
            $data = $this->getImageCollection()->findOne(array(
                'publicKey' => $publicKey,
                'imageIdentifier' => $imageIdentifier,
            ));

            if ($data === null) {
                throw new DatabaseException('Image not found', 404);
            }

            $this->getImageCollection()->update(
                array('publicKey' => $publicKey, 'imageIdentifier' => $imageIdentifier),
                array('$set' => array('metadata' => array(), 'metadata_n' => array())),
                array('multiple' => false)
            );
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to delete meta data', 500, $e);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getImages($publicKey, Query $query, Images $model) {
        // Initialize return value
        $images = array();

        // Query data
        $queryData = array(
            'publicKey' => $publicKey,
        );

        $from = $query->from();
        $to = $query->to();

        if ($from || $to) {
            $tmp = array();

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

        if ($metadataQuery = $query->metadataQuery()) {
            $queryData = array_merge($queryData, $this->prepareMetadataQuery($metadataQuery));
        }

        // Sorting
        $sort = array('added' => -1);

        if ($querySort = $query->sort()) {
            $sort = array();

            foreach ($querySort as $s) {
                $sort[$s['field']] = ($s['sort'] === 'asc' ? 1 : -1);
            }
        }

        // Fields to fetch
        $fields = array('extension', 'added', 'checksum', 'originalChecksum', 'updated', 'publicKey', 'imageIdentifier', 'mime', 'size', 'width', 'height');

        if ($query->returnMetadata()) {
            $fields[] = 'metadata';
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
    public function load($publicKey, $imageIdentifier, Image $image) {
        try {
            $data = $this->getImageCollection()->findOne(
                array('publicKey' => $publicKey, 'imageIdentifier' => $imageIdentifier),
                array('size', 'width', 'height', 'mime', 'extension', 'added', 'updated')
            );
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to fetch image data', 500, $e);
        }

        if ($data === null) {
            throw new DatabaseException('Image not found', 404);
        }

        $image->setWidth($data['width'])
              ->setHeight($data['height'])
              ->setMimeType($data['mime'])
              ->setExtension($data['extension'])
              ->setAddedDate(new DateTime('@' . $data['added'], new DateTimeZone('UTC')))
              ->setUpdatedDate(new DateTime('@' . $data['updated'], new DateTimeZone('UTC')));

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastModified($publicKey, $imageIdentifier = null) {
        try {
            // Query on the public key
            $query = array('publicKey' => $publicKey);

            if ($imageIdentifier) {
                // We want information about a single image. Add the identifier to the query
                $query['imageIdentifier'] = $imageIdentifier;
            }

            // Create the cursor
            $cursor = $this->getImageCollection()->find($query, array('updated'))
                                                 ->limit(1)
                                                 ->sort(array(
                                                     'updated' => MongoCollection::DESCENDING,
                                                 ));

            // Fetch the next row
            $data = $cursor->getNext();
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to fetch image data', 500, $e);
        }

        if ($data === null && $imageIdentifier) {
            throw new DatabaseException('Image not found', 404);
        } else if ($data === null) {
            $data = array('updated' => time());
        }

        return new DateTime('@' . $data['updated'], new DateTimeZone('UTC'));
    }

    /**
     * {@inheritdoc}
     */
    public function getNumImages($publicKey) {
        try {
            $query = array(
                'publicKey' => $publicKey,
            );

            $result = (int) $this->getImageCollection()->find($query)->count();

            return $result;
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to fetch information from the database', 500, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNumBytes($publicKey) {
        try {
            $result = $this->getImageCollection()->aggregate(
                array('$match' => array('publicKey' => $publicKey)),
                array('$group' => array('_id' => null, 'numBytes' => array('$sum' => '$size')))
            )['result'];

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
    public function getImageMimeType($publicKey, $imageIdentifier) {
        try {
            $data = $this->getImageCollection()->findOne(array(
                'publicKey' => $publicKey,
                'imageIdentifier' => $imageIdentifier,
            ));
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
    public function imageExists($publicKey, $imageIdentifier) {
        $data = $this->getImageCollection()->findOne(array(
            'publicKey' => $publicKey,
            'imageIdentifier' => $imageIdentifier,
        ));

        return $data !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function insertShortUrl($shortUrlId, $publicKey, $imageIdentifier, $extension = null, array $query = array()) {
        try {
            $this->getShortUrlCollection()->insert(array(
                'shortUrlId' => $shortUrlId,
                'publicKey' => $publicKey,
                'imageIdentifier' => $imageIdentifier,
                'extension' => $extension,
                'query' => serialize($query),
            ));
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to create short URL', 500, $e);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getShortUrlId($publicKey, $imageIdentifier, $extension = null, array $query = array()) {
        try {
            $result = $this->getShortUrlCollection()->findOne(array(
                'publicKey' => $publicKey,
                'imageIdentifier' => $imageIdentifier,
                'extension' => $extension,
                'query' => serialize($query),
            ), array(
                'shortUrlId',
            ));

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
            $result = $this->getShortUrlCollection()->findOne(array(
                'shortUrlId' => $shortUrlId,
            ), array(
                '_id' => null,
                'publicKey',
                'imageIdentifier',
                'extension',
                'query',
            ));

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
    public function deleteShortUrls($publicKey, $imageIdentifier, $shortUrlId = null) {
        $query = array(
            'publicKey' => $publicKey,
            'imageIdentifier' => $imageIdentifier,
        );

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

    /**
     * Prepare a metadata query for the MongoDB adapter
     *
     * This method will prefix all field names with "metadata_n.", and will translate the custom
     * $wildcard operator to $regex
     *
     * @param array $query The metadata query from the query string
     * @return array
     */
    private function prepareMetadataQuery(array $query) {
        $result = array();

        foreach ($query as $key => $value) {
            if (!is_numeric($key) && substr($key, 0, 1) !== '$') {
                $key = 'metadata_n.' . $key;
            } else if ($key === '$wildcard') {
                $key = '$regex';
                $value = $this->getWildcardRegex($value);
            }

            if (is_array($value)) {
                $value = $this->prepareMetadataQuery($value);
            }

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Lowercase an array, both keys and values
     *
     * @param array $data The data to lowercase
     * @return array
     */
    private function lowercaseArray(array $data) {
        $result = array();

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = $this->lowercaseArray($value);
            } else if (is_string($value)) {
                $value = strtolower($value);
            }

            $result[strtolower($key)] = $value;
        }

        return $result;
    }

    /**
     * Get a regex search
     *
     * @param string $query A query string with possible wildcards represented as '*'
     * @return MongoRegex
     */
    private function getWildcardRegex($query) {
        // Replace any * with something that will not be quoted
        $query = str_replace('*', $this->regexWildcardReplacement, $query);
        $query = '/^' . preg_quote($query, '/') . '$/';
        $query = str_replace(array(
            $this->regexWildcardReplacement,
            '_'
        ), array(
            '.*',
            '.'
        ), $query);

        return new MongoRegex($query);
    }
}
