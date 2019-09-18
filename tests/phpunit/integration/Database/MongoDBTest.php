<?php declare(strict_types=1);
namespace ImboIntegrationTest\Database;

use Imbo\Database\MongoDB;
use MongoDB\Client as MongoClient;

/**
 * @coversDefaultClass Imbo\Database\MongoDB
 */
class MongoDBTest extends DatabaseTests {
    /**
     * Name of the test database
     *
     * @var string
     */
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
        if (!class_exists('MongoDB\Client')) {
            $this->markTestSkipped('pecl/mongodb >= 1.1.3 is required to run this test');
        }

        $client = new MongoClient();
        $client->dropDatabase($this->databaseName);
        $client->selectCollection($this->databaseName, 'image')->createIndex(
            ['user' => 1, 'imageIdentifier' => 1],
            ['unique' => true]
        );

        parent::setUp();
    }

    protected function tearDown() : void {
        if (class_exists('MongoDB\Client')) {
            (new MongoClient())->dropDatabase($this->databaseName);
        }

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
