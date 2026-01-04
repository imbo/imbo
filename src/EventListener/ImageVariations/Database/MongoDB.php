<?php declare(strict_types=1);
namespace Imbo\EventListener\ImageVariations\Database;

use Imbo\Exception\DatabaseException;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Driver\Exception\Exception as MongoDBException;

class MongoDB implements DatabaseInterface
{
    public const IMAGE_VARIATION_COLLECTION_NAME = 'imagevariation';

    private Collection $collection;

    /**
     * Create a new MongoDB database adapter
     *
     * @param string $databaseName The name of the database to use
     * @param string $uri The URI to use when connecting to MongoDB
     * @param array<mixed> $uriOptions Options for the URI, sent to the MongoDB\Client instance
     * @param array<mixed> $driverOptions Additional options for the MongoDB\Client instance
     * @param ?Client $client Pre-configured MongoDB client. When specified $uri, $uriOptions and $driverOptions are ignored
     * @throws DatabaseException
     */
    public function __construct(
        string $databaseName = 'imbo',
        string $uri          = 'mongodb://localhost:27017',
        array $uriOptions    = [],
        array $driverOptions = [],
        ?Client $client      = null,
    ) {
        try {
            $client = $client ?: new Client($uri, $uriOptions, $driverOptions);
        } catch (MongoDBException $e) {
            throw new DatabaseException('Unable to connect to the database', 500, $e);
        }

        $this->collection = $client->selectCollection(
            $databaseName,
            self::IMAGE_VARIATION_COLLECTION_NAME,
        );
    }

    public function storeImageVariationMetadata(string $user, string $imageIdentifier, int $width, int $height): true
    {
        try {
            $this->collection->insertOne([
                'added' => time(),
                'user' => $user,
                'imageIdentifier'  => $imageIdentifier,
                'width' => $width,
                'height' => $height,
            ]);
        } catch (MongoDBException $e) {
            throw new DatabaseException('Unable to save image variation data', 500, $e);
        }

        return true;
    }

    public function getBestMatch(string $user, string $imageIdentifier, int $width): ?array
    {
        $query = [
            'user' => $user,
            'imageIdentifier' => $imageIdentifier,
            'width' => [
                '$gte' => $width,
            ],
        ];

        /** @var ?array{width:int,height:int} */
        $document = $this->collection->findOne($query, [
            'projection' => [
                '_id'    => 0,
                'width'  => 1,
                'height' => 1,
            ],
            'sort' => [
                'width' => 1,
            ],
        ]);

        return null === $document ? null : [
            'width' => $document['width'],
            'height' => $document['height'],
        ];
    }

    public function deleteImageVariations(string $user, string $imageIdentifier, ?int $width = null): bool
    {
        $query = [
            'user' => $user,
            'imageIdentifier' => $imageIdentifier,
        ];

        if (null !== $width) {
            $query['width'] = $width;
        }

        $this->collection->deleteMany($query);

        return true;
    }
}
