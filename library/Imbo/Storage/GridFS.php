<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Storage;

use Imbo\Exception\StorageException,
    Mongo,
    MongoClient,
    MongoGridFS,
    MongoException,
    DateTime,
    DateTimeZone;

/**
 * GridFS (MongoDB) database driver
 *
 * A GridFS storage driver for Imbo
 *
 * Valid parameters for this driver:
 *
 * - <pre>(string) databaseName</pre> Name of the database. Defaults to 'imbo_storage'
 * - <pre>(string) server</pre> The server string to use when connecting to MongoDB. Defaults to
 *                              'mongodb://localhost:27017'
 * - <pre>(array) options</pre> Options to use when creating the Mongo client instance. Defaults to
 *                              ['connect' => true, 'connectTimeoutMS' => 1000].
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Storage
 */
class GridFS implements StorageInterface {
    /**
     * Mongo client instance
     *
     * @var MongoClient
     */
    private $mongoClient;

    /**
     * The grid instance
     *
     * @var MongoGridFS
     */
    private $grid;

    /**
     * Parameters for the driver
     *
     * @var array
     */
    private $params = [
        // Database name
        'databaseName' => 'imbo_storage',

        // Server string and ctor options
        'server'  => 'mongodb://localhost:27017',
        'options' => ['connect' => true, 'connectTimeoutMS' => 1000],
    ];

    /**
     * Class constructor
     *
     * @param array $params Parameters for the driver
     * @param MongoClient $client Mongo client instance
     * @param MongoGridFS $grid MongoGridFS instance
     */
    public function __construct(array $params = null, MongoClient $client = null, MongoGridFS $grid = null) {
        if ($params !== null) {
            $this->params = array_replace_recursive($this->params, $params);
        }

        if ($client !== null) {
            $this->mongoClient = $client;
        }

        if ($grid !== null) {
            $this->grid = $grid;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function store($user, $imageIdentifier, $imageData) {
        $now = time();

        if ($this->imageExists($user, $imageIdentifier)) {
            $this->getGrid()->update(
                ['user' => $user, 'imageIdentifier' => $imageIdentifier],
                ['$set' => ['updated' => $now]]
            );

            return true;
        }

        $metadata = [
            'user' => $user,
            'imageIdentifier' => $imageIdentifier,
            'updated' => $now,
        ];

        $this->getGrid()->storeBytes($imageData, $metadata);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($user, $imageIdentifier) {
        if (($file = $this->getImageObject($user, $imageIdentifier)) === false) {
            throw new StorageException('File not found', 404);
        }

        $this->getGrid()->delete($file->file['_id']);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getImage($user, $imageIdentifier) {
        if (($file = $this->getImageObject($user, $imageIdentifier)) === false) {
            throw new StorageException('File not found', 404);
        }

        return $file->getBytes();
    }

    /**
     * {@inheritdoc}
     */
    public function getLastModified($user, $imageIdentifier) {
        if (($file = $this->getImageObject($user, $imageIdentifier)) === false) {
            throw new StorageException('File not found', 404);
        }

        $timestamp = $file->file['updated'];

        return new DateTime('@' . $timestamp, new DateTimeZone('UTC'));
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus() {
        try {
            return $this->getMongoClient()->connect();
        } catch (StorageException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function imageExists($user, $imageIdentifier) {
        $cursor = $this->getGrid()->find([
            'user' => $user,
            'imageIdentifier' => $imageIdentifier
        ]);

        return (boolean) $cursor->count();
    }

    /**
     * Get the grid instance
     *
     * @return MongoGridFS
     */
    protected function getGrid() {
        if ($this->grid === null) {
            try {
                $database = $this->getMongoClient()->selectDB($this->params['databaseName']);
                $this->grid = $database->getGridFS();
            } catch (MongoException $e) {
                throw new StorageException('Could not connect to database', 500, $e);
            }
        }

        return $this->grid;
    }

    /**
     * Get the mongo client instance
     *
     * @return MongoClient
     */
    protected function getMongoClient() {
        if ($this->mongoClient === null) {
            try {
                $this->mongoClient = new MongoClient($this->params['server'], $this->params['options']);
            } catch (MongoException $e) {
                throw new StorageException('Could not connect to database', 500, $e);
            }
        }

        return $this->mongoClient;
    }

    /**
     * Get an image object
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier The image identifier
     * @return boolean|MongoGridFSFile Returns false if the file does not exist or an instance of
     *                                 MongoGridFSFile if the file exists
     */
    private function getImageObject($user, $imageIdentifier) {
        $cursor = $this->getGrid()->find([
            'user' => $user,
            'imageIdentifier' => $imageIdentifier
        ]);

        if ($cursor->count()) {
            return $cursor->getNext();
        }

        return false;
    }
}
