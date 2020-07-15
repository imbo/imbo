<?php declare(strict_types=1);
namespace Imbo\Storage;

use Imbo\Exception\StorageException;
use MongoDB\Client;
use MongoDB\Database;
use MongoDB\GridFS\Bucket;
use MongoDB\Driver\Exception\Exception as MongoDBException;
use MongoDB\Driver\Command;
use MongoDB\Model\BSONDocument;
use DateTime;
use DateTimeZone;

/**
 * GridFS (MongoDB) storage driver for images
 *
 * Valid parameters for this driver:
 *
 * - `string uri`: MongoDB connection string. Defaults to 'mongodb://localhost:27017'
 * - `array uriOptions`: Additional connection string options. Defaults to []
 * - `array clientOptions`: Driver-specific options for the internal MongoDB client. Defaults to
 *                          ['connect' => true, 'connectTimeoutMS' => 1000]
 * - `string databaseName`: Name of the database to connect to. Defaults to 'imbo_storage'
 * - `array bucketOptions`: Options for the internal Bucket instance. Defaults to []
 */
class GridFS implements StorageInterface {
    private Client $client;
    private Database $database;
    private Bucket $bucket;

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
        } catch (MongoDBException $e) {
            throw new StorageException('Could not connect to database', 500, $e);
        }

        $this->database = $this->client->selectDatabase($this->params['databaseName']);
        $this->bucket = $this->database->selectGridFSBucket($this->params['bucketOptions']);
    }

    /**
     * {@inheritdoc}
     */
    public function store(string $user, string $imageIdentifier, string $imageData) : bool {
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

        $this->bucket->uploadFromStream(
            $this->getImageFilename($user, $imageIdentifier),
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
    public function delete(string $user, string $imageIdentifier) : bool {
        if (($file = $this->getImageObject($user, $imageIdentifier)) === null) {
            throw new StorageException('File not found', 404);
        }

        $this->bucket->delete($file['_id']);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getImage(string $user, string $imageIdentifier) : ?string {
        try {
            return stream_get_contents($this->bucket->openDownloadStreamByName(
                $this->getImageFilename($user, $imageIdentifier)
            ));
        } catch (MongoDBException $e) {
            throw new StorageException('File not found', 404, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLastModified(string $user, string $imageIdentifier) : DateTime {
        if (($file = $this->getImageObject($user, $imageIdentifier)) === null) {
            throw new StorageException('File not found', 404);
        }

        return new DateTime(sprintf('@%d', $file['metadata']['updated']), new DateTimeZone('UTC'));
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus() : bool {
        try {
            $cursor = $this->client
                ->getManager()
                ->executeCommand($this->params['databaseName'], new Command(['ping' => 1]));

            return (bool) $cursor->toArray()[0]->ok;
        } catch (MongoDBException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function imageExists(string $user, string $imageIdentifier) : bool {
        return null !== $this->bucket->findOne([
            'metadata.user' => $user,
            'metadata.imageIdentifier' => $imageIdentifier
        ]);
    }

    /**
     * Get an image object
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier The image identifier
     * @return ?BSONDocument Returns null if the file does not exist or the file as an object otherwise
     */
    protected function getImageObject(string $user, string $imageIdentifier) : ?BSONDocument {
        return $this->bucket->findOne([
            'metadata.user' => $user,
            'metadata.imageIdentifier' => $imageIdentifier,
        ]);
    }

    /**
     * Create a stream for a string
     *
     * @param string $data The string to use in the stream
     * @return resource
     */
    private function createStream(string $data) {
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, $data);
        rewind($stream);

        return $stream;
    }

    /**
     * Get the image filename
     *
     * @param string $user
     * @param string $imageIdentifier
     * @return string
     */
    private function getImageFilename($user, $imageIdentifier) {
        return sprintf('%s.%s', $user, $imageIdentifier);
    }
}
