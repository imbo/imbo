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

use Imbo\Database\Mongo,
    MongoDB\Client;

/**
 * @covers Imbo\Database\MongoDB
 * @group integration
 * @group database
 * @group mongodb
 */

class MongoTest extends DatabaseTests {
    protected $databaseName = 'imboIntegrationTestDatabase';

    /**
     * @see ImboIntegrationTest\Database\DatabaseTests::getAdapter()
     */
    protected function getAdapter() {
        return new Mongo([
            'databaseName' => $this->databaseName,
        ]);
    }

    /**
     * Make sure we have the mongo extension available and drop the test database just in case
     */
    public function setUp() {
        if (!class_exists('MongoDB\Client')) {
            $this->markTestSkipped('pecl/mongodb >= 1.1.2 is required to run this test');
        }

        $client = new Client();
        $client->selectDatabase($this->databaseName)->drop();

        parent::setUp();
    }

    /**
     * Drop the test database after each test
     */
    public function tearDown() {
        if (class_exists('MongoDB\Client')) {
            $client = new Client();
            $client->selectDatabase($this->databaseName)->drop();
        }

        parent::tearDown();
    }

    /**
     * @covers Imbo\Database\MongoDB::getStatus
     */
    public function testReturnsFalseWhenFetchingStatusAndTheHostnameIsNotCorrect() {
        $db = new Mongo([
            'server' => 'foobar',
        ]);

        $this->assertFalse($db->getStatus());
    }
}
