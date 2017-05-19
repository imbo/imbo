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
    MongoDB\Client,
    MongoDB\GridFS\Database,
    MongoDB\GridFS\Bucket,
    MongoDB\Driver\Exception\Exception as MongoException,
    MongoDB\Driver\Command,
    DateTime,
    DateTimeZone;

/**
 * GridFS (MongoDB) storage driver for images
 *
 * Parameters for this driver:
 *
 * - `uri`: MongoDB connection string. Defaults to 'mongodb://localhost:27017'
 * - `uriOptions`: Additional connection string options. Defaults to []
 * - `clientOptions`: Driver-specific options for the internal MongoDB client. Defaults to
 *                    ['connect' => true, 'connectTimeoutMS' => 1000]
 * - `databaseName`: Name of the database to connect to
 * - `bucketOptions`: Options for the internal Bucket instance. Defaults to []
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Storage
 */
class GridFS implements StorageInterface {
    /**
     * @var Client
     */
    private $client;

    /**
     * @var Database
     */
    private $database;

    /**
     * @var Bucket
     */
    private $bucket;

    /**
     * Parameters for the driver
     *
     * @var array
     */
    private $params = [
        'uri' => 'mongodb://localhost:27017',
        'uriOptions' => [],
        'clientOptions' => [
            'connect' => true,
            'connectTimeoutMS' => 1000,
        ],
        'databaseName' => 'imbo_storage',
        'bucketOptions' => [],
    ];

    /**
     * Class constructor
     *
     * @param array $params Parameters for the driver
     * @param Client $client MongoDB client instance
     * @param Bucket $bucket Bucket instance
     */
    public function __construct(array $params = null) {
        if ($params !== null) {
            $this->params = array_replace_recursive($this->params, $params);
        }

        try {
            $this->client = new Client(
                $this->params['uri'],
                $this->params['uriOptions'],
                $this->params['clientOptions']
            );
        } catch (MongoException $e) {
            throw new StorageException('Could not connect to database', 500, $e);
        }

        $this->database = $this->client->selectDatabase($this->params['databaseName']);
        $this->bucket = $this->database->selectGridFSBucket($this->params['bucketOptions']);
    }

    /**
     * {@inheritdoc}
     */
    public function store($user, $imageIdentifier, $imageData) {
        $now = time();

        if ($this->imageExists($user, $imageIdentifier)) {
            // Fetch the files part of the bucket to manipulate metadata
            $collectionName = sprintf('%s.files', $this->bucket->getBucketName());
            $collection = $this->database->selectCollection($collectionName);
            $collection->updateOne([
                'metadata.user' => $user,
                'metadata.imageIdentifier' => $imageIdentifier
            ], [
                '$set' => [
                    'metadata.updated' => $now
                ],
            ]);

            return true;
        }

        $result = $this->bucket->uploadFromStream(
            $user . '.' . $imageIdentifier,
            $this->createStream($imageData),
            [
                'metadata' => [
                    'user' => $user,
                    'imageIdentifier' => $imageIdentifier,
                    'updated' => $now,
                ],
            ]
        );

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($user, $imageIdentifier) {
        if (($file = $this->getImageObject($user, $imageIdentifier)) === false) {
            throw new StorageException('File not found', 404);
        }

        $this->bucket->delete($file['_id']);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getImage($user, $imageIdentifier) {
        if (($file = $this->getImageObject($user, $imageIdentifier)) === false) {
            throw new StorageException('File not found', 404);
        }

        return stream_get_contents($this->bucket->openDownloadStream($file['_id']));
    }

    /**
     * {@inheritdoc}
     */
    public function getLastModified($user, $imageIdentifier) {
        if (($file = $this->getImageObject($user, $imageIdentifier)) === false) {
            throw new StorageException('File not found', 404);
        }

        $timestamp = $file['metadata']->getArrayCopy()['updated'];

        return new DateTime('@' . $timestamp, new DateTimeZone('UTC'));
    }

    /**
     * {@inheritdoc}
     * @fixed
     */
    public function getStatus() {
        try {
            return $this->client
                ->getManager()
                ->executeCommand($this->params['databaseName'], new Command(['ping' => 1]));
        } catch (MongoException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     * @fixed
     */
    public function imageExists($user, $imageIdentifier) {
        $cursor = $this->bucket->findOne([
            'metadata.user' => $user,
            'metadata.imageIdentifier' => $imageIdentifier
        ]);

        return $cursor !== null;
    }

    /**
     * Get an image object
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier The image identifier
     * @return boolean|array Returns false if the file does not exist or the file as an array otherwise
     */
    protected function getImageObject($user, $imageIdentifier) {
        $cursor = $this->bucket->find([
            'metadata.user' => $user,
            'metadata.imageIdentifier' => $imageIdentifier,
        ], ['limit' => 1]);

        foreach ($cursor as $file) {
            // Return first entry
            return $file->getArrayCopy();
        }

        return false;
    }

    /**
     * Create a stream for a string
     *
     * @param string $data The string to use in the stream
     * @return resource
     */
    private function createStream($data) {
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, $data);
        rewind($stream);

        return $stream;
    }
}
