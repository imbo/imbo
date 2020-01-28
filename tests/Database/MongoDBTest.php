<?php declare(strict_types=1);
namespace Imbo\Database;

use MongoDB\Client as MongoClient;

/**
 * @coversDefaultClass Imbo\Database\MongoDB
 */
class MongoDBTest extends DatabaseTests {
    protected $databaseName = 'imboIntegrationTestDatabase';

    protected function getAdapter() : MongoDB {
        return new MongoDB([
            'databaseName' => $this->databaseName,
        ]);
    }

    protected function insertImage(array $image) : void {
        (new MongoClient)->selectCollection($this->databaseName, 'image')->insertOne([
            'user'             => $image['user'],
            'imageIdentifier'  => $image['imageIdentifier'],
            'size'             => $image['size'],
            'extension'        => $image['extension'],
            'mime'             => $image['mime'],
            'added'            => $image['added'],
            'updated'          => $image['updated'],
            'width'            => $image['width'],
            'height'           => $image['height'],
            'checksum'         => $image['checksum'],
            'originalChecksum' => $image['originalChecksum'],
        ]);
    }

    public function setUp() : void {
        $client = new MongoClient();
        $client->dropDatabase($this->databaseName);
        $client
            ->selectCollection($this->databaseName, 'image')
            ->createIndex(
                ['user' => 1, 'imageIdentifier' => 1],
                ['unique' => true]
            );

        parent::setUp();
    }

    public function tearDown() : void {
        (new MongoClient())->dropDatabase($this->databaseName);

        parent::tearDown();
    }

    /**
     * @covers ::getStatus
     */
    public function testReturnsFalseWhenFetchingStatusAndTheHostnameIsNotCorrect() : void {
        $db = new MongoDB([
            'server' => 'mongodb://localhost:11111',
        ]);
        $this->assertFalse($db->getStatus());
    }
}
