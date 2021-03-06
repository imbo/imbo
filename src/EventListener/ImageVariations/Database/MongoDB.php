<?php
namespace Imbo\EventListener\ImageVariations\Database;

use Imbo\Exception\DatabaseException;
use MongoDB\Client as MongoClient;
use MonboDB\Collection as MongoCollection;
use MongoDB\Driver\Exception\Exception as MongoException;

/**
 * MongoDB database driver for the image variations
 *
 * Valid parameters for this driver:
 *
 * - (string) databaseName Name of the database. Defaults to 'imbo'
 * - (string) server The server string to use when connecting to MongoDB. Defaults to
 *                   'mongodb://localhost:27017'
 * - (array) options Options to use when creating the MongoClient instance. Defaults to
 *                   ['connect' => true, 'connectTimeoutMS' => 1000].
 */
class MongoDB implements DatabaseInterface {
    /**
     * Mongo client instance
     *
     * @var MongoClient
     */
    private $mongoClient;

    /**
     * The imagevariation collection
     *
     * @var MongoCollection
     */
    private $collection;

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
     * @param MongoCollection $collection MongoCollection instance for the image variation collection
     */
    public function __construct(array $params = null, MongoClient $client = null, MongoCollection $collection = null) {
        if ($params !== null) {
            $this->params = array_replace_recursive($this->params, $params);
        }

        if ($client !== null) {
            $this->mongoClient = $client;
        }

        if ($collection !== null) {
            $this->collection = $collection;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function storeImageVariationMetadata($user, $imageIdentifier, $width, $height) {
        try {
            $this->getCollection()->insertOne([
                'added' => time(),
                'user' => $user,
                'imageIdentifier'  => $imageIdentifier,
                'width' => $width,
                'height' => $height,
            ]);
        } catch (MongoException $e) {
            throw new DatabaseException('Unable to save image variation data', 500, $e);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getBestMatch($user, $imageIdentifier, $width) {
        $query = [
            'user' => $user,
            'imageIdentifier' => $imageIdentifier,
            'width' => [
                '$gte' => $width,
            ],
        ];

        $result = $this->getCollection()
            ->findOne($query, [
                'projection' => [
                    '_id' => false,
                    'width' => true,
                    'height' => true,
                ],
                'sort' => [
                    'width' => 1,
                ],
            ]);

        return $result ? $result->getArrayCopy() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteImageVariations($user, $imageIdentifier, $width = null) {
        $query = [
            'user' => $user,
            'imageIdentifier' => $imageIdentifier,
        ];

        if ($width !== null) {
            $query['width'] = $width;
        }

        $this->getCollection()->deleteMany($query);

        return true;
    }

    /**
     * Get the mongo collection
     *
     * @return MongoCollection
     */
    private function getCollection() {
        if ($this->collection === null) {
            try {
                $this->collection = $this->getMongoClient()->selectCollection(
                    $this->params['databaseName'],
                    'imagevariation'
                );
            } catch (MongoException $e) {
                throw new DatabaseException('Could not select collection', 500, $e);
            }
        }

        return $this->collection;
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
