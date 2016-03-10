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

use Imbo\Auth\AccessControl\Adapter\Mongo,
    MongoDB\Client as MongoClient;

/**
 * @covers Imbo\Auth\AccessControl\Adapter\Mongo
 * @group integration
 * @group mongo
 */
class MongoTest extends AdapterTests {
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
        return new Mongo([
            'databaseName' => $this->databaseName,
        ]);
    }

    /**
     * Make sure we have the mongo extension available and drop the test database just in case
     */
    public function setUp() {
        if (!class_exists('MongoDB\Client')) {
            $this->markTestSkipped('pecl/mongodb >= 1.1.3 is required to run this test');
        }

        $client = new MongoClient();
        $client->dropDatabase($this->databaseName);

        parent::setUp();
    }

    /**
     * Drop the test database after each test
     */
    public function tearDown() {
        if (class_exists('MongoDB\Client')) {
            $client = new MongoClient();
            $client->dropDatabase($this->databaseName);
        }

        parent::tearDown();
    }
}
