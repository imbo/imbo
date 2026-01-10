<?php declare(strict_types=1);

namespace Imbo\Auth\AccessControl\Adapter;

use Imbo\Exception\DatabaseException;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Driver\Exception\Exception as MongoDBException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[CoversClass(MongoDB::class)]
class MongoDBTest extends TestCase
{
    private Collection&MockObject $aclCollection;
    private Collection&MockObject $aclGroupCollection;
    private MongoDB $adapter;

    protected function setUp(): void
    {
        $this->aclCollection = $this->createMock(Collection::class);
        $this->aclGroupCollection = $this->createMock(Collection::class);

        $database = $this->createStub(Database::class);
        $database
            ->method('selectCollection')
            ->willReturnCallback(
                fn (string $collectionName): Collection&MockObject => match ($collectionName) {
                    MongoDB::ACL_COLLECTION_NAME => $this->aclCollection,
                    MongoDB::ACL_GROUP_COLLECTION_NAME => $this->aclGroupCollection,
                    default => $this->fail(sprintf('Unknown collection name: %s', $collectionName)),
                },
            );
        $this->adapter = new MongoDB(client: $this->createConfiguredMock(Client::class, [
            'selectDatabase' => $database,
        ]));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionWhenUnableToAddKeyPair(): void
    {
        $e = $this->createStub(MongoDBException::class);
        $this->aclCollection
            ->expects($this->once())
            ->method('insertOne')
            ->willThrowException($e);

        $this->expectExceptionObject(new DatabaseException('Unable to insert key', 500, $e));
        $this->adapter->addKeyPair('pub', 'priv');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionWhenUnableToDeleteKeyPair(): void
    {
        $e = $this->createStub(MongoDBException::class);
        $this->aclCollection
            ->expects($this->once())
            ->method('deleteOne')
            ->willThrowException($e);

        $this->expectExceptionObject(new DatabaseException('Unable to delete key', 500, $e));
        $this->adapter->deletePublicKey('pub');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionWhenUnableToUpdatePrivateKey(): void
    {
        $e = $this->createStub(MongoDBException::class);
        $this->aclCollection
            ->expects($this->once())
            ->method('updateOne')
            ->willThrowException($e);

        $this->expectExceptionObject(new DatabaseException('Unable to update private key', 500, $e));
        $this->adapter->updatePrivateKey('pub', 'priv');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionWhenUnableToAddAccessRule(): void
    {
        $e = $this->createStub(MongoDBException::class);
        $this->aclCollection
            ->expects($this->once())
            ->method('updateOne')
            ->willThrowException($e);

        $this->expectExceptionObject(new DatabaseException('Unable to add access rule', 500, $e));
        $this->adapter->addAccessRule('pub', [
            'resources' => ['resource'],
            'users' => ['user'],
        ]);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionWhenUnableToDeleteAccessRule(): void
    {
        $e = $this->createStub(MongoDBException::class);
        $this->aclCollection
            ->expects($this->once())
            ->method('updateOne')
            ->willThrowException($e);

        $this->expectExceptionObject(new DatabaseException('Unable to delete access rule', 500, $e));
        $this->adapter->deleteAccessRule('pub', '67f61fb747dec72627014d20');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionWhenUnableToAddResourceGroup(): void
    {
        $e = $this->createStub(MongoDBException::class);
        $this->aclGroupCollection
            ->expects($this->once())
            ->method('insertOne')
            ->willThrowException($e);

        $this->expectExceptionObject(new DatabaseException('Unable to add resource group', 500, $e));
        $this->adapter->addResourceGroup('group', ['group']);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionWhenUnableToUpdateResourceGroup(): void
    {
        $e = $this->createStub(MongoDBException::class);
        $this->aclGroupCollection
            ->expects($this->once())
            ->method('updateOne')
            ->willThrowException($e);

        $this->expectExceptionObject(new DatabaseException('Unable to update resource group', 500, $e));
        $this->adapter->updateResourceGroup('group', ['group']);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionWhenUnableToDeleteResourceGroup(): void
    {
        $e = $this->createStub(MongoDBException::class);
        $this->aclGroupCollection
            ->expects($this->once())
            ->method('deleteOne')
            ->willThrowException($e);

        $this->expectExceptionObject(new DatabaseException('Unable to delete resource group', 500, $e));
        $this->adapter->deleteResourceGroup('group');
    }
}
