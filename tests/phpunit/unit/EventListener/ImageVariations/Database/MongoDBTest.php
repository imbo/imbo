<?php declare(strict_types=1);
namespace Imbo\EventListener\ImageVariations\Database;

use MongoDB\Client as MongoClient;

/**
 * @coversDefaultClass Imbo\EventListener\ImageVariations\Database\MongoDB
 */
class MongoDBTest extends DatabaseTests {
    private $databaseName = 'imboIntegrationTestDatabase';

    protected function getAdapter() {
        return new MongoDB([
            'databaseName' => $this->databaseName,
        ]);
    }

    public function setUp() : void {
        (new MongoClient())->dropDatabase($this->databaseName);

        parent::setUp();
    }

    protected function tearDown() : void {
        parent::tearDown();
    }
}
