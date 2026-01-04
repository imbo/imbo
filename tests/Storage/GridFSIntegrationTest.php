<?php declare(strict_types=1);
namespace Imbo\Storage;

use MongoDB\Client;
use MongoDB\Driver\Exception\RuntimeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass(GridFS::class)]
#[Group('integration')]
class GridFSIntegrationTest extends StorageTests
{
    private string $databaseName = 'imbo-mongodb-adapters-integration-test';

    protected function getAdapter(): GridFS
    {
        $uriOptions = array_filter([
            'username' => (string) getenv('MONGODB_USERNAME'),
            'password' => (string) getenv('MONGODB_PASSWORD'),
        ]);

        $uri = (string) getenv('MONGODB_URI');

        return new GridFS($this->databaseName, $uri, $uriOptions);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $uriOptions = array_filter([
            'username' => (string) getenv('MONGODB_USERNAME'),
            'password' => (string) getenv('MONGODB_PASSWORD'),
        ]);

        $uri = (string) getenv('MONGODB_URI');
        $client = new Client($uri, $uriOptions);

        try {
            $client->getDatabase($this->databaseName)->command(['ping' => 1]);
        } catch (RuntimeException) {
            $this->markTestSkipped('MongoDB is not running, start it with `docker compose up -d`', );
        }

        $client->dropDatabase($this->databaseName);
    }
}
