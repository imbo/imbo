<?php declare(strict_types=1);

namespace Imbo\EventListener\ImageVariations\Storage;

use ImboSDK\EventListener\ImageVariations\Storage\StorageTests;
use MongoDB\Client;
use MongoDB\Driver\Exception\Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;

#[CoversClass(GridFS::class)]
#[Group('integration')]
#[RequiresEnvironmentVariable('MONGODB_URI')]
class GridFSIntegrationTest extends StorageTests
{
    private const DATABASE_NAME = 'imbo-imagevariations-integration-test';

    protected function getAdapter(): GridFS
    {
        $uri = (string) getenv('MONGODB_URI');

        try {
            $client = new Client($uri);
            $client->dropDatabase(self::DATABASE_NAME);
        } catch (Exception) {
            $this->markTestSkipped('MongoDB database is not available.');
        }

        return new GridFS(self::DATABASE_NAME, $uri);
    }
}
