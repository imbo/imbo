<?php declare(strict_types=1);
namespace Imbo\Storage;

use MongoDB\Client;

/**
 * @coversDefaultClass Imbo\Storage\GridFS
 */
class GridFSTest extends StorageTests {
    private $databaseName = 'imboGridFSIntegrationTest';

    protected function getDriver() {
        return new GridFS([
            'databaseName' => $this->databaseName,
        ]);
    }

    public function setUp() : void {
        (new Client())->dropDatabase($this->databaseName);
        parent::setUp();
    }

    protected function tearDown() : void {
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
