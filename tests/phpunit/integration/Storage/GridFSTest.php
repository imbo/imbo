<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboIntegrationTest\Storage;

use Imbo\Storage\GridFS,
    MongoClient;

/**
 * @covers Imbo\Storage\GridFS
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
    private $testDbName = 'imboGridFSIntegrationTest';

    /**
     * @see ImboIntegrationTest\Storage\StorageTests::getDriver()
     */
    protected function getDriver() {
        return new GridFS([
            'databaseName' => $this->testDbName,
        ]);
    }

    public function setUp() {
        if (!class_exists('MongoClient')) {
            $this->markTestSkipped('pecl/mongo >= 1.3.0 is required to run this test');
        }

        $client = new MongoClient();
        $client->selectDB($this->testDbName)->drop();

        parent::setUp();
    }

    public function tearDown() {
        if (class_exists('MongoClient')) {
            $client = new MongoClient();
            $client->selectDB($this->testDbName)->drop();
        }

        parent::tearDown();
    }

    /**
     * @covers Imbo\Storage\GridFS::getStatus
     */
    public function testReturnsFalseWhenFetchingStatusAndTheHostnameIsNotCorrect() {
        $storage = new GridFS([
            'server' => 'foobar',
        ]);
        $this->assertFalse($storage->getStatus());
    }
}
