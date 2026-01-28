<?php declare(strict_types=1);

namespace Imbo\EventListener\ImageVariations\Database;

use ImboSDK\EventListener\ImageVariations\Database\DatabaseTests;
use MongoDB\Client;
use MongoDB\Driver\Exception\Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;

#[CoversClass(MongoDB::class)]
#[Group('integration')]
#[RequiresEnvironmentVariable('MONGODB_URI')]
class MongoDBIntegrationTest extends DatabaseTests
{
    private const DATABASE_NAME = 'imbo-imagevariations-integration-test';

    protected function getAdapter(): DatabaseInterface
    {
        $uri = (string) getenv('MONGODB_URI');

        try {
            $client = new Client($uri);
            $client->dropDatabase(self::DATABASE_NAME);
        } catch (Exception) {
            $this->markTestSkipped('MongoDB database is not available.');
        }

        return new MongoDB(self::DATABASE_NAME, $uri);
    }
}
