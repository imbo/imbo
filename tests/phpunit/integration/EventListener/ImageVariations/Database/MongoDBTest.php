<?php declare(strict_types=1);
namespace ImboIntegrationTest\EventListener\ImageVariations\Database;

use Imbo\EventListener\ImageVariations\Database\MongoDB;
use MongoDB\Client as MongoClient;

/**
 * @coversDefaultClass Imbo\EventListener\ImageVariations\Database\MongoDB
 */
class MongoDBTest extends DatabaseTests {
    private $databaseName = 'imboIntegrationTestDatabase';

    /**
     * @see ImboIntegrationTest\EventListener\ImageVariations\Database\DatabaseTests::getAdapter()
     */
    protected function getAdapter() {
        return new MongoDB([
            'databaseName' => $this->databaseName,
        ]);
    }

    /**
     * Make sure we have the mongo extension available and drop the test database just in case
     */
    public function setUp() : void {
        if (!class_exists('MongoDB\Client')) {
            $this->markTestSkipped('pecl/mongodb >= 1.1.3 is required to run this test');
        }

        (new MongoClient())->dropDatabase($this->databaseName);

        parent::setUp();
    }

    /**
     * Drop the test database after each test
     */
    protected function tearDown() : void {
        if (class_exists('MongoDB\Client')) {
            (new MongoClient())->dropDatabase($this->databaseName);
        }

        parent::tearDown();
    }
}
