<?php declare(strict_types=1);
namespace Imbo\EventListener\ImageVariations\Storage;

use MongoDB\Client;

/**
 * @coversDefaultClass Imbo\EventListener\ImageVariations\Storage\GridFS
 */
class GridFSTest extends StorageTests {
    private $databaseName = 'imboGridFSIntegrationTest';

    protected function getAdapter() {
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
}
