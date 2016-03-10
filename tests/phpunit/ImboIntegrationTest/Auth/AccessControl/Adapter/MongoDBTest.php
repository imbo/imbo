<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboIntegrationTest\Auth\AccessControl\Adapter;

use Imbo\Auth\AccessControl\Adapter\MongoDB,
    MongoClient;

/**
 * @covers Imbo\Auth\AccessControl\Adapter\MongoDB
 * @group integration
 * @group mongodb
 */
class MongoDBTest extends AdapterTests {
    /**
     * Name of the test database
     *
     * @var string
     */
    protected $databaseName = 'imboIntegrationTestAuth';

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
}
