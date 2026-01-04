<?php declare(strict_types=1);
namespace Imbo\Database;

use MongoDB\Client;
use MongoDB\Driver\Exception\RuntimeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass(MongoDB::class)]
#[Group('integration')]
class MongoDBIntegrationTest extends DatabaseTests
{
    private string $databaseName = 'imbo-database-mongodb-integration-test';

    protected function getAdapter(): DatabaseInterface
    {
        return new MongoDB(
            $this->databaseName,
            (string) getenv('MONGODB_URI'),
            array_filter([
                'username' => (string) getenv('MONGODB_USERNAME'),
                'password' => (string) getenv('MONGODB_PASSWORD'),
            ]),
        );
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
        $client->selectCollection($this->databaseName, MongoDB::IMAGE_COLLECTION_NAME)->createIndex([
            'user'            => 1,
            'imageIdentifier' => 1,
        ], [
            'unique' => true,
        ]);
    }
}
