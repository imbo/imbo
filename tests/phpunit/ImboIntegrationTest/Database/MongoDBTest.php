<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboIntegrationTest\Database;

use Imbo\Database\MongoDB,
    MongoClient;

/**
 * @covers Imbo\Database\MongoDB
 * @group integration
 * @group database
 * @group mongodb
 */
class MongoDBTest extends DatabaseTests {
    protected $databaseName = 'imboIntegrationTestDatabase';

    /**
     * @see ImboIntegrationTest\Database\DatabaseTests::getAdapter()
     */
    protected function getAdapter() {
        return new MongoDB([
            'databaseName' => $this->databaseName,
        ]);
    }

    /**
     * Make sure we have the mongo extension available and drop the test database just in case
     */
    public function setUp() {
        if (!class_exists('MongoClient')) {
            $this->markTestSkipped('pecl/mongo >= 1.3.0 is required to run this test');
        }

        $client = new MongoClient();
        $client->selectDB($this->databaseName)->drop();

        parent::setUp();
    }

    /**
     * Drop the test database after each test
     */
    public function tearDown() {
        if (class_exists('MongoClient')) {
            $client = new MongoClient();
            $client->selectDB($this->databaseName)->drop();
        }

        parent::tearDown();
    }

    /**
     * @covers Imbo\Database\MongoDB::getStatus
     */
    public function testReturnsFalseWhenFetchingStatusAndTheHostnameIsNotCorrect() {
        $db = new MongoDB([
            'server' => 'foobar',
        ]);
        $this->assertFalse($db->getStatus());
    }
}
