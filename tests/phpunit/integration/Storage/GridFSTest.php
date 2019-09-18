<?php declare(strict_types=1);
namespace ImboIntegrationTest\Storage;

use Imbo\Storage\GridFS;
use MongoDB\Client;

/**
 * @coversDefaultClass Imbo\Storage\GridFS
 */
class GridFSTest extends StorageTests {
    /**
     * Name of the test db
     *
     * @var string
     */
    private $databaseName = 'imboGridFSIntegrationTest';

    /**
     * @see ImboIntegrationTest\Storage\StorageTests::getDriver()
     */
    protected function getDriver() {
        return new GridFS([
            'databaseName' => $this->databaseName,
        ]);
    }

    public function setUp() : void {
        if (!class_exists('MongoDB\Driver\Manager') || !class_exists('MongoDB\Client')) {
            $this->markTestSkipped('pecl/mongodb and mongodb/mongodb are both required to run this test');
        }

        (new Client())->dropDatabase($this->databaseName);

        parent::setUp();
    }

    protected function tearDown() : void {
        if (class_exists('MongoDB\Client')) {
            (new Client())->dropDatabase($this->databaseName);
        }

        parent::tearDown();
    }

    /**
     * @covers ::getStatus
     */
    public function testReturnsFalseWhenFetchingStatusAndTheHostnameIsNotCorrect() : void {
        $storage = new GridFS([
            'uri' => 'mongodb://localhost:1',
        ]);
        $this->assertFalse($storage->getStatus());
    }
}
