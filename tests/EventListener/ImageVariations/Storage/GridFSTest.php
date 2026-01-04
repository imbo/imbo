<?php declare(strict_types=1);
namespace Imbo\EventListener\ImageVariations\Storage;

use Imbo\Exception\StorageException;
use MongoDB\Client;
use MongoDB\Database;
use MongoDB\Driver\Exception\Exception as MongoDBException;
use MongoDB\Driver\Exception\RuntimeException as DriverRuntimeException;
use MongoDB\GridFS\Bucket;
use MongoDB\GridFS\Exception\FileNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(GridFS::class)]
class GridFSTest extends TestCase
{
    private string $user    = 'user';
    private string $imageId = 'image-id';

    public function testThrowsExceptionWhenInvalidUriIsSpecified(): void
    {
        $this->expectExceptionObject(new StorageException('Unable to connect to the database', 500));
        new GridFS('some-database', 'foo');
    }

    public function testCanStoreImageVariation(): void
    {
        $bucketOptions = ['some' => 'option'];

        $bucket = $this->createMock(Bucket::class);
        $bucket
            ->expects($this->once())
            ->method('uploadFromStream')
            ->with(
                'user.image-id.100',
                $this->isResource(),
                $this->callback(
                    fn (array $data): bool =>
                        is_int($data['metadata']['added'] ?? null) &&
                        $this->user === ($data['metadata']['user'] ?? null) &&
                        $this->imageId === ($data['metadata']['imageIdentifier'] ?? null) &&
                        100 === ($data['metadata']['width'] ?? null),
                ),
            );

        $database = $this->createMock(Database::class);
        $database
            ->expects($this->once())
            ->method('selectGridFSBucket')
            ->with($bucketOptions)
            ->willReturn($bucket);

        $client = $this->createMock(Client::class);
        $client
            ->expects($this->once())
            ->method('selectDatabase')
            ->with('database-name')
            ->willReturn($database);

        $adapter = new GridFS('database-name', bucketOptions: $bucketOptions, client: $client);
        $adapter->storeImageVariation($this->user, $this->imageId, 'image data', 100);
    }

    public function testCanGetImageVariation(): void
    {
        $stream = fopen('php://temp', 'w');

        if (!$stream) {
            $this->fail('Unable to open stream');
        }

        fwrite($stream, 'image data');
        rewind($stream);

        $bucket = $this->createMock(Bucket::class);
        $bucket
            ->expects($this->once())
            ->method('openDownloadStreamByName')
            ->with('user.image-id.100')
            ->willReturn($stream);

        $client = $this->createConfiguredStub(Client::class, [
            'selectDatabase' => $this->createConfiguredStub(Database::class, [
                'selectGridFSBucket' => $bucket,
            ]),
        ]);

        $adapter = new GridFS('database-name', client: $client);
        $this->assertSame(
            'image data',
            $adapter->getImageVariation($this->user, $this->imageId, 100),
        );
    }

    #[DataProvider('getGetImageExceptions')]
    public function testGetImageVariationThrowsExceptionWhenErrorOccurs(MongoDBException $mongoDbException, StorageException $storageException): void
    {
        $bucket = $this->createMock(Bucket::class);
        $bucket
            ->expects($this->once())
            ->method('openDownloadStreamByName')
            ->willThrowException($mongoDbException);

        $client = $this->createConfiguredStub(Client::class, [
            'selectDatabase' => $this->createConfiguredStub(Database::class, [
                'selectGridFSBucket' => $bucket,
            ]),
        ]);

        $adapter = new GridFS('database-name', client: $client);
        $this->expectExceptionObject($storageException);
        $adapter->getImageVariation($this->user, $this->imageId, 100);
    }

    /**
     * @return array<array{mongoDbException:MongoDBException,storageException:StorageException}>
     */
    public static function getGetImageExceptions(): array
    {
        return [
            [
                'mongoDbException' => new FileNotFoundException('some error'),
                'storageException' => new StorageException('File not found', 404),
            ],
            [
                'mongoDbException' => new DriverRuntimeException('some error'),
                'storageException' => new StorageException('Unable to get image', 500),
            ],
        ];
    }
}
