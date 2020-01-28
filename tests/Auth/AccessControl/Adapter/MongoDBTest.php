<?php declare(strict_types=1);
namespace Imbo\Auth\AccessControl\Adapter;

use MongoDB\Client as MongoClient;

/**
 * @coversDefaultClass Imbo\Auth\AccessControl\Adapter\MongoDB
 */
class MongoDBTest extends AdapterTests {
    protected $databaseName = 'imboIntegrationTestAuth';

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
