<?php declare(strict_types=1);
namespace Imbo\Auth\AccessControl\Adapter;

use MongoDB\Client;
use MongoDB\Driver\Exception\RuntimeException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MongoDB::class)]
class MongoDBIntegrationTest extends MutableAdapterTests
{
    private string $databaseName = 'imbo-auth-accesscontrol-adapter-mongodb-integration-test';

    protected function getAdapter(): MongoDB
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
    }
}
