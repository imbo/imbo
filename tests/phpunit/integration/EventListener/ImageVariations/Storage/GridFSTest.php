<?php
namespace ImboIntegrationTest\EventListener\ImageVariations\Storage;

use Imbo\EventListener\ImageVariations\Storage\GridFS;
use MongoDB\Client;

/**
 * @covers Imbo\EventListener\ImageVariations\Storage\GridFS
 * @group integration
 * @group storage
 * @group mongodb
 */
class GridFSTest extends StorageTests {
    /**
     * Name of the test db
     *
     * @var string
     */
    private $databaseName = 'imboGridFSIntegrationTest';

    /**
     * @see ImboIntegrationTest\EventListener\ImageVariations\Storage\StorageTests::getAdapter()
     */
    protected function getAdapter() {
        return new GridFS([
            'databaseName' => $this->databaseName,
        ]);
    }

    /**
     * Make sure we have the mongo extension available and drop the test database just in case
     */
    public function setUp() : void {
        if (!class_exists('MongoDB\Driver\Manager') || !class_exists('MongoDB\Client')) {
            $this->markTestSkipped('pecl/mongodb and mongodb/mongodb are both required to run this test');
        }

        (new Client())->dropDatabase($this->databaseName);

        parent::setUp();
    }

    /**
     * Drop the test database after each test
     */
    protected function tearDown() : void {
        if (!class_exists('MongoDB\Driver\Manager') || !class_exists('MongoDB\Client')) {
            (new Client())->dropDatabase($this->databaseName);
        }

        parent::tearDown();
    }
}
