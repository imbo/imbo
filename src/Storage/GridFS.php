<?php declare(strict_types=1);
namespace Imbo\Storage;

use DateTime;
use DateTimeZone;
use Imbo\Exception\StorageException;
use MongoDB\Client;
use MongoDB\Database;
use MongoDB\Driver\Exception\Exception as MongoDBException;
use MongoDB\GridFS\Bucket;
use MongoDB\GridFS\Exception\FileNotFoundException;
use MongoDB\Model\BSONDocument;

class GridFS implements StorageInterface
{
    private Bucket $bucket;
    private Database $database;

    /**
     * Class constructor
     *
     * @param string $databaseName The name of the database to use
     * @param string $uri The URI to use when connecting to MongoDB
     * @param array<mixed> $uriOptions Options for the URI, sent to the MongoDB\Client instance
     * @param array<mixed> $driverOptions Additional options for the MongoDB\Client instance
     * @param array<mixed> $bucketOptions Options for the bucket operations
     * @param ?Client $client Pre-configured MongoDB client. When specified $uri, $uriOptions and $driverOptions are ignored
     */
    public function __construct(
        string $databaseName = 'imbo_storage',
        string $uri          = 'mongodb://localhost:27017',
        array $uriOptions    = [],
        array $driverOptions = [],
        array $bucketOptions = [],
        ?Client $client      = null,
    ) {
        try {
            $client = $client ?: new Client($uri, $uriOptions, $driverOptions);
        } catch (MongoDBException $e) {
            throw new StorageException('Unable to connect to the database', 500, $e);
        }

        $this->database = $client->selectDatabase($databaseName);
        $this->bucket = $this->database->selectGridFSBucket($bucketOptions);
    }

    public function store(string $user, string $imageIdentifier, string $imageData): true
    {
        if ($this->imageExists($user, $imageIdentifier)) {
            $collection = $this->database->selectCollection(
                $this->bucket->getBucketName() . '.files',
            );

            try {
                $collection->updateOne([
                    'metadata.user'            => $user,
                    'metadata.imageIdentifier' => $imageIdentifier,
                ], [
                    '$set' => [
                        'metadata.updated' => time(),
                    ],
                ]);
            } catch (MongoDBException $e) {
                throw new StorageException('Unable to update image', 500, $e);
            }

            return true;
        }

        try {
            $this->bucket->uploadFromStream(
                $this->getImageFilename($user, $imageIdentifier),
                $this->createStream($imageData),
                [
                    'metadata' => [
                        'user'            => $user,
                        'imageIdentifier' => $imageIdentifier,
                        'updated'         => time(),
                    ],
                ],
            );
        } catch (MongoDBException $e) {
            throw new StorageException('Unable to store image', 500, $e);
        }

        return true;
    }

    public function delete(string $user, string $imageIdentifier): true
    {
        /** @var array{_id:string} */
        $file = $this->getImageObject($user, $imageIdentifier);
        $this->bucket->delete($file['_id']);

        return true;
    }

    public function getImage(string $user, string $imageIdentifier): ?string
    {
        try {
            return stream_get_contents($this->bucket->openDownloadStreamByName(
                $this->getImageFilename($user, $imageIdentifier),
            )) ?: null;
        } catch (FileNotFoundException $e) {
            throw new StorageException('File not found', 404, $e);
        } catch (MongoDBException $e) {
            throw new StorageException('Unable to get image', 500, $e);
        }
    }

    public function getLastModified(string $user, string $imageIdentifier): DateTime
    {
        /** @var array{metadata:array{updated:int}} */
        $file = $this->getImageObject($user, $imageIdentifier);
        return new DateTime('@' . $file['metadata']['updated'], new DateTimeZone('UTC'));
    }

    public function getStatus(): bool
    {
        try {
            $this->database->command(['ping' => 1]);
        } catch (MongoDBException $e) {
            return false;
        }

        return true;
    }

    public function imageExists(string $user, string $imageIdentifier): bool
    {
        try {
            return null !== $this->bucket->findOne([
                'metadata.user'            => $user,
                'metadata.imageIdentifier' => $imageIdentifier,
            ]);
        } catch (MongoDBException $e) {
            throw new StorageException('Unable to check if image exists', 500, $e);
        }
    }

    /**
     * @return array<string,mixed>
     */
    protected function getImageObject(string $user, string $imageIdentifier): array
    {
        /** @var ?BSONDocument */
        $document = $this->bucket->findOne([
            'metadata.user'            => $user,
            'metadata.imageIdentifier' => $imageIdentifier,
        ]);

        if (null === $document) {
            throw new StorageException('File not found', 404);
        }

        return $document->getArrayCopy();
    }

    /**
     * Create a stream for a string
     *
     * @throws StorageException
     * @return resource
     */
    private function createStream(string $data)
    {
        $stream = fopen('php://temp', 'w+b');

        if (false === $stream) {
            throw new StorageException('Unable to open stream', 500);
        }

        fwrite($stream, $data);
        rewind($stream);

        return $stream;
    }

    private function getImageFilename(string $user, string $imageIdentifier): string
    {
        return $user . '.' . $imageIdentifier;
    }
}
