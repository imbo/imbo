<?php declare(strict_types=1);
namespace Imbo\EventListener\ImageVariations\Database;

use MongoDB\Client;
use MongoDB\Driver\Exception\RuntimeException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MongoDB::class)]
class MongoDBIntegrationTest extends DatabaseTests
{
    private string $databaseName = 'imbo-imagevariations-integration-test';

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
    }
}
