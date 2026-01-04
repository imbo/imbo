<?php declare(strict_types=1);
namespace Imbo\EventListener\ImageVariations\Storage;

use Imbo\Exception\StorageException;
use MongoDB\Client;
use MongoDB\Driver\Exception\Exception as MongoDBException;
use MongoDB\GridFS\Bucket;
use MongoDB\GridFS\Exception\FileNotFoundException;

class GridFS implements StorageInterface
{
    private Bucket $bucket;

    /**
     * Create a new GridFS storage adapter
     *
     * @param string $databaseName The name of the database to use
     * @param string $uri The URI to use when connecting to MongoDB
     * @param array<mixed> $uriOptions Options for the URI, sent to the MongoDB\Client instance
     * @param array<mixed> $driverOptions Additional options for the MongoDB\Client instance
     * @param array<mixed> $bucketOptions Options for the bucket operations
     * @param ?Client $client Pre-configured MongoDB client. When specified $uri, $uriOptions and $driverOptions are ignored
     * @throws StorageException
     */
    public function __construct(
        string $databaseName = 'imbo_imagevariation_storage',
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

        $this->bucket = $client
            ->selectDatabase($databaseName)
            ->selectGridFSBucket($bucketOptions);
    }

    public function storeImageVariation(string $user, string $imageIdentifier, string $blob, int $width): true
    {
        try {
            $this->bucket->uploadFromStream(
                $this->getImageFilename($user, $imageIdentifier, $width),
                $this->createStream($blob),
                [
                    'metadata' => [
                        'added'           => time(),
                        'user'            => $user,
                        'imageIdentifier' => $imageIdentifier,
                        'width'           => $width,
                    ],
                ],
            );
        } catch (MongoDBException $e) {
            throw new StorageException('Unable to store image variation', 500, $e);
        }

        return true;
    }

    public function getImageVariation(string $user, string $imageIdentifier, int $width): ?string
    {
        try {
            return stream_get_contents($this->bucket->openDownloadStreamByName(
                $this->getImageFilename($user, $imageIdentifier, $width),
            )) ?: null;
        } catch (FileNotFoundException $e) {
            throw new StorageException('File not found', 404, $e);
        } catch (MongoDBException $e) {
            throw new StorageException('Unable to get image variation', 500, $e);
        }
    }

    public function deleteImageVariations(string $user, string $imageIdentifier, ?int $width = null): true
    {
        $filter = [
            'metadata.user'            => $user,
            'metadata.imageIdentifier' => $imageIdentifier,
        ];

        if (null !== $width) {
            $filter['metadata.width'] = $width;
        }

        /** @var iterable<array{_id:string}> */
        $files = $this->bucket->find($filter);

        foreach ($files as $file) {
            try {
                $this->bucket->delete($file['_id']);
            } catch (MongoDBException $e) {
                throw new StorageException('Unable to delete image variations', 500, $e);
            }
        }

        return true;
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

    private function getImageFilename(string $user, string $imageIdentifier, int $width): string
    {
        return $user . '.' . $imageIdentifier . '.' . $width;
    }
}
