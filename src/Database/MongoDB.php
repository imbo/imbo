<?php declare(strict_types=1);
namespace Imbo\Database;

use DateTime;
use Imbo\Exception\DatabaseException;
use Imbo\Exception\DuplicateImageIdentifierException;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Helpers\BSONToArray;
use Imbo\Model\Image;
use Imbo\Model\Images;
use Imbo\Resource\Images\Query;
use MongoDB\BSON\ObjectId as MongoId;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Driver\Exception\BulkWriteException;
use MongoDB\Driver\Exception\Exception as MongoDBException;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;

/**
 * @phpstan-type ImageData array{
 *     _id:MongoId,
 *     size:int,
 *     width:int,
 *     height:int,
 *     metadata:BSONArray|BSONDocument,
 *     added:int,
 *     updated:int,
 *     extension:string,
 *     mime:string
 * }
 */
class MongoDB implements DatabaseInterface
{
    public const IMAGE_COLLECTION_NAME      = 'image';
    public const SHORT_URLS_COLLECTION_NAME = 'shortUrl';

    private Database $database;
    private Collection $imageCollection;
    private Collection $shortUrlCollection;

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
        private string $databaseName = 'imbo',
        string $uri                  = 'mongodb://localhost:27017',
        array $uriOptions            = [],
        array $driverOptions         = [],
        ?Client $client              = null,
    ) {
        try {
            $client = $client ?: new Client($uri, $uriOptions, $driverOptions);
        } catch (MongoDBException $e) {
            throw new DatabaseException('Unable to connect to the database', 500, $e);
        }

        $this->database = $client->selectDatabase($this->databaseName);
        $this->imageCollection = $this->database->selectCollection(self::IMAGE_COLLECTION_NAME);
        $this->shortUrlCollection = $this->database->selectCollection(self::SHORT_URLS_COLLECTION_NAME);
    }

    public function insertImage(string $user, string $imageIdentifier, Image $image, bool $updateIfDuplicate = true): bool
    {
        $now = time();

        if ($updateIfDuplicate && $this->imageExists($user, $imageIdentifier)) {
            try {
                $this->imageCollection->updateOne(
                    [
                        'user'            => $user,
                        'imageIdentifier' => $imageIdentifier,
                    ],
                    [
                        '$set' => [
                            'updated' => $now,
                        ],
                    ],
                );
            } catch (MongoDBException $e) {
                throw new DatabaseException('Unable to save image data', 500, $e);
            }

            return true;
        }

        $added   = $image->getAddedDate();
        $updated = $image->getUpdatedDate();

        $data = [
            'size'             => $image->getFilesize(),
            'user'             => $user,
            'imageIdentifier'  => $imageIdentifier,
            'extension'        => $image->getExtension(),
            'mime'             => $image->getMimeType(),
            'metadata'         => [],
            'added'            => $added ? $added->getTimestamp() : $now,
            'updated'          => $updated ? $updated->getTimestamp() : $now,
            'width'            => $image->getWidth(),
            'height'           => $image->getHeight(),
            'checksum'         => $image->getChecksum(),
            'originalChecksum' => $image->getOriginalChecksum(),
        ];

        try {
            $this->imageCollection->insertOne($data);
        } catch (BulkWriteException $e) {
            if (11000 === $e->getCode()) {
                throw new DuplicateImageIdentifierException(
                    'Duplicate image identifier when attempting to insert image into DB.',
                    503,
                );
            }

            throw new DatabaseException('Unable to save image data', 500, $e);
        } catch (MongoDBException $e) {
            throw new DatabaseException('Unable to save image data', 500, $e);
        }

        return true;
    }

    public function deleteImage(string $user, string $imageIdentifier): bool
    {
        // Get image to potentially trigger an exception if the image does not exist
        $this->getImageData($user, $imageIdentifier);

        try {
            $this->imageCollection->deleteOne([
                'user'            => $user,
                'imageIdentifier' => $imageIdentifier,
            ]);
        } catch (MongoDBException $e) {
            throw new DatabaseException('Unable to delete image data', 500, $e);
        }

        return true;
    }

    public function updateMetadata(string $user, string $imageIdentifier, array $metadata): bool
    {
        try {
            $this->imageCollection->updateOne(
                [
                    'user'            => $user,
                    'imageIdentifier' => $imageIdentifier,
                ],
                [
                    '$set' => [
                        'metadata' => array_merge(
                            $this->getMetadata($user, $imageIdentifier),
                            $metadata,
                        ),
                    ],
                ],
            );
        } catch (MongoDBException $e) {
            throw new DatabaseException('Unable to update meta data', 500, $e);
        }

        return true;
    }

    public function getMetadata(string $user, string $imageIdentifier): array
    {
        $metadata = $this->getImageData($user, $imageIdentifier)['metadata'];

        /** @var array<string,mixed> */
        return (new BSONToArray())->toArray($metadata);
    }

    public function deleteMetadata(string $user, string $imageIdentifier): bool
    {
        // Get image to potentially trigger an exception if the image does not exist
        $this->getImageData($user, $imageIdentifier);

        try {
            $this->imageCollection->updateOne(
                [
                    'user'            => $user,
                    'imageIdentifier' => $imageIdentifier,
                ],
                [
                    '$set' => [
                        'metadata' => [],
                    ],
                ],
            );
        } catch (MongoDBException $e) {
            throw new DatabaseException('Unable to delete meta data', 500, $e);
        }

        return true;
    }

    public function getImages(array $users, Query $query, Images $model): array
    {
        $images    = [];
        $queryData = [];

        if (!empty($users)) {
            $queryData['user']['$in'] = $users;
        }

        $from = $query->getFrom();
        $to   = $query->getTo();

        if ($from || $to) {
            $tmp = [];

            if (null !== $from) {
                $tmp['$gte'] = $from;
            }

            if (null !== $to) {
                $tmp['$lte'] = $to;
            }

            $queryData['added'] = $tmp;
        }

        $imageIdentifiers = $query->getImageIdentifiers();

        if (!empty($imageIdentifiers)) {
            $queryData['imageIdentifier']['$in'] = $imageIdentifiers;
        }

        $checksums = $query->getChecksums();

        if (!empty($checksums)) {
            $queryData['checksum']['$in'] = $checksums;
        }

        $originalChecksums = $query->getOriginalChecksums();

        if (!empty($originalChecksums)) {
            $queryData['originalChecksum']['$in'] = $originalChecksums;
        }

        $fields = array_fill_keys([
            'extension',
            'added',
            'checksum',
            'originalChecksum',
            'updated',
            'user',
            'imageIdentifier',
            'mime',
            'size',
            'width',
            'height',
        ], 1);
        $fields['_id'] = 0;

        $sort = ['added' => -1];

        if (!empty($query->getSort())) {
            $sort = [];

            foreach ($query->getSort() as $s) {
                if (!array_key_exists($s['field'], $fields)) {
                    throw new DatabaseException(sprintf('Invalid sort field: %s', $s['field']), 400);
                }

                $sort[$s['field']] = ('asc' === $s['sort'] ? 1 : -1);
            }
        }

        if ($query->getReturnMetadata()) {
            $fields['metadata'] = 1;
        }

        try {
            $options = [
                'projection' => $fields,
                'limit'      => $query->getLimit(),
                'sort'       => $sort,
            ];

            if (($page = $query->getPage()) > 1) {
                if (!$query->getLimit()) {
                    throw new DatabaseException('page is not allowed without limit', 400);
                }

                $skip = $query->getLimit() * ($page - 1);
                $options['skip'] = $skip;
            }

            /** @var iterable<array{added:int,updated:int}> */
            $result = $this->imageCollection->find($queryData, $options);
            $model->setHits($this->imageCollection->countDocuments($queryData));
        } catch (MongoDBException $e) {
            throw new DatabaseException('Unable to search for images', 500, $e);
        }

        foreach ($result as $image) {
            $image['added']   = new DateTime('@' . $image['added']);
            $image['updated'] = new DateTime('@' . $image['updated']);
            $images[] = (new BSONToArray())->toArray($image);
        }

        return $images;
    }

    public function getImageProperties(string $user, string $imageIdentifier): array
    {
        $data = $this->getImageData($user, $imageIdentifier);

        return [
            'size'      => $data['size'],
            'width'     => $data['width'],
            'height'    => $data['height'],
            'mime'      => $data['mime'],
            'extension' => $data['extension'],
            'added'     => $data['added'],
            'updated'   => $data['updated'],
        ];
    }

    public function load(string $user, string $imageIdentifier, Image $image): bool
    {
        $data = $this->getImageData($user, $imageIdentifier);

        $image
            ->setWidth($data['width'])
            ->setHeight($data['height'])
            ->setFilesize($data['size'])
            ->setMimeType($data['mime'])
            ->setExtension($data['extension'])
            ->setAddedDate(new DateTime('@' . $data['added']))
            ->setUpdatedDate(new DateTime('@' . $data['updated']));

        return true;
    }

    public function getLastModified(array $users, ?string $imageIdentifier = null): DateTime
    {
        $query = [];

        if (!empty($users)) {
            $query['user']['$in'] = $users;
        }

        if (null !== $imageIdentifier) {
            $query['imageIdentifier'] = $imageIdentifier;
        }

        try {
            /** @var ?array{updated:int} */
            $document = $this->imageCollection->findOne($query, [
                'sort' => [
                    'updated' => -1,
                ],
                'projection' => [
                    'updated' => 1,
                ],
            ]);
        } catch (MongoDBException $e) {
            throw new DatabaseException('Unable to fetch image data', 500, $e);
        }

        if (null === $document && null !== $imageIdentifier) {
            throw new DatabaseException('Image not found', 404);
        }

        $updated = null === $document ? time() : $document['updated'];

        return new DateTime('@' . $updated);
    }

    public function setLastModifiedNow(string $user, string $imageIdentifier): DateTime
    {
        return $this->setLastModifiedTime($user, $imageIdentifier, new DateTime('@' . time()));
    }

    public function setLastModifiedTime(string $user, string $imageIdentifier, DateTime $time): DateTime
    {
        if (!$this->imageExists($user, $imageIdentifier)) {
            throw new DatabaseException('Image not found', 404);
        }

        // @todo Check if mongodb throws not found exception on updateOne
        $this->imageCollection->updateOne(
            [
                'user'            => $user,
                'imageIdentifier' => $imageIdentifier,
            ],
            [
                '$set' => [
                    'updated' => $time->getTimestamp(),
                ],
            ],
        );

        return $time;
    }

    public function getNumImages(?string $user = null): int
    {
        $query = [];

        if (null !== $user) {
            $query['user'] = $user;
        }

        try {
            $result = $this->imageCollection->countDocuments($query);
        } catch (MongoDBException $e) {
            throw new DatabaseException('Unable to fetch information from the database', 500, $e);
        }

        return $result;
    }

    public function getNumBytes(?string $user = null): int
    {
        $pipeline = [];

        if (null !== $user) {
            $pipeline[] = [
                '$match' => [
                    'user' => $user,
                ],
            ];
        }

        $pipeline[] = [
            '$group' => [
                '_id' => null,
                'numBytes' => [
                    '$sum' => '$size',
                ],
            ],
        ];

        try {
            /** @var iterable<array{numBytes:int}> */
            $result = $this->imageCollection->aggregate($pipeline);
        } catch (MongoDBException $e) {
            throw new DatabaseException('Unable to fetch information from the database', 500, $e);
        }

        $sum = 0;

        foreach ($result as $document) {
            $sum += $document['numBytes'];
        }

        return $sum;
    }

    public function getNumUsers(): int
    {
        try {
            return count($this->imageCollection->distinct('user'));
        } catch (MongoDBException $e) {
            throw new DatabaseException('Unable to fetch information from the database', 500, $e);
        }
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

    public function getImageMimeType(string $user, string $imageIdentifier): string
    {
        return $this->getImageData($user, $imageIdentifier)['mime'];
    }

    public function imageExists(string $user, string $imageIdentifier): bool
    {
        try {
            $this->getImageData($user, $imageIdentifier);
        } catch (DatabaseException $e) {
            if (404 === $e->getCode()) {
                return false;
            }

            throw $e;
        }

        return true;
    }

    public function insertShortUrl(string $shortUrlId, string $user, string $imageIdentifier, ?string $extension = null, array $query = []): bool
    {
        try {
            $this->shortUrlCollection->insertOne([
                'shortUrlId'      => $shortUrlId,
                'user'            => $user,
                'imageIdentifier' => $imageIdentifier,
                'extension'       => $extension,
                'query'           => serialize($query),
            ]);
        } catch (MongoDBException $e) {
            throw new DatabaseException('Unable to create short URL', 500, $e);
        }

        return true;
    }

    public function getShortUrlId(string $user, string $imageIdentifier, ?string $extension = null, array $query = []): ?string
    {
        try {
            /** @var ?array{shortUrlId:string} */
            $document = $this->shortUrlCollection->findOne([
                'user'            => $user,
                'imageIdentifier' => $imageIdentifier,
                'extension'       => $extension,
                'query'           => serialize($query),
            ], [
                'shortUrlId' => 1,
            ]);
        } catch (MongoDBException $e) {
            return null;
        }

        return $document['shortUrlId'] ?? null;
    }

    public function getShortUrlParams(string $shortUrlId): ?array
    {
        try {
            /** @var ?array{user:string,imageIdentifier:string,extension:string,query?:string} */
            $document = $this->shortUrlCollection->findOne([
                'shortUrlId' => $shortUrlId,
            ], [
                '_id' => false,
            ]);
        } catch (MongoDBException $e) {
            return null;
        }

        if (null === $document) {
            return null;
        }

        if (empty($document['query'])) {
            throw new DatabaseException('Missing query from result', 500);
        }

        /** @var array<string,string|array<string>> */
        $query = unserialize($document['query']);

        return [
            'user'            => $document['user'],
            'imageIdentifier' => $document['imageIdentifier'],
            'extension'       => $document['extension'],
            'query'           => $query,
        ];
    }

    public function deleteShortUrls(string $user, string $imageIdentifier, ?string $shortUrlId = null): bool
    {
        $query = [
            'user'            => $user,
            'imageIdentifier' => $imageIdentifier,
        ];

        if (null !== $shortUrlId) {
            $query['shortUrlId'] = $shortUrlId;
        }

        try {
            $this->shortUrlCollection->deleteMany($query);
        } catch (MongoDBException $e) {
            throw new DatabaseException('Unable to delete short URLs', 500, $e);
        }

        return true;
    }

    public function getAllUsers(): array
    {
        /** @var array<string> */
        return $this->imageCollection->distinct('user');
    }

    /**
     * Get image data
     *
     * @return ImageData
     * @throws DatabaseException
     */
    private function getImageData(string $user, string $imageIdentifier): array
    {
        try {
            $image = $this->imageCollection->findOne([
                'user'            => $user,
                'imageIdentifier' => $imageIdentifier,
            ]);
        } catch (MongoDBException $e) {
            throw new DatabaseException('Unable to find image data', 500, $e);
        }

        if (null === $image || !$image instanceof BSONDocument) {
            throw new DatabaseException('Image not found', 404);
        }

        return $this->toImageData($image);
    }

    /**
     * Convert a BSONDocument to a known associative array
     *
     * @throws InvalidArgumentException
     * @return ImageData
     */
    private function toImageData(BSONDocument $document): array
    {
        if (!$document['_id'] instanceof MongoId) {
            throw new InvalidArgumentException(sprintf('Expected "_id" to be an instance of %s.', MongoId::class));
        }

        if (!is_int($document['size'])) {
            throw new InvalidArgumentException('Expected "size" to be an integer.');
        }

        if (!is_int($document['width'])) {
            throw new InvalidArgumentException('Expected "width" to be an integer.');
        }

        if (!is_int($document['height'])) {
            throw new InvalidArgumentException('Expected "height" to be an integer.');
        }

        if (!is_int($document['added'])) {
            throw new InvalidArgumentException('Expected "added" to be an integer.');
        }

        if (!is_int($document['updated'])) {
            throw new InvalidArgumentException('Expected "updated" to be an integer.');
        }

        if (!$document['metadata'] instanceof BSONArray && !$document['metadata'] instanceof BSONDocument) {
            throw new InvalidArgumentException(sprintf('Expected "metadata" to be an instance of %s or %s.', BSONArray::class, BSONDocument::class));
        }

        if (!is_string($document['mime'])) {
            throw new InvalidArgumentException('Expected "mime" to be a string.');
        }

        if (!is_string($document['extension'])) {
            throw new InvalidArgumentException('Expected "extension" to be a string.');
        }

        return [
            '_id'       => $document['_id'],
            'size'      => $document['size'],
            'width'     => $document['width'],
            'height'    => $document['height'],
            'metadata'  => $document['metadata'],
            'mime'      => $document['mime'],
            'extension' => $document['extension'],
            'added'     => $document['added'],
            'updated'   => $document['updated'],
        ];
    }
}
