<?php declare(strict_types=1);

namespace Imbo\Auth\AccessControl\Adapter;

use ImboSDK\Auth\AccessControl\Adapter\MutableAdapterTests;
use MongoDB\Client;
use MongoDB\Driver\Exception\Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;

#[CoversClass(MongoDB::class)]
#[Group('integration')]
#[RequiresEnvironmentVariable('MONGODB_URI')]
class MongoDBIntegrationTest extends MutableAdapterTests
{
    private const DATABASE_NAME = 'imbo-auth-accesscontrol-adapter-mongodb-integration-test';

    protected function getAdapter(): MongoDB
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
