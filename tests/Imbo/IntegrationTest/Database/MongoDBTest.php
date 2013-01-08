<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\IntegrationTest\Database;

use Imbo\Database\MongoDB,
    Mongo;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Integration tests
 * @covers Imbo\Database\MongoDB
 */
class MongoDBTest extends DatabaseTests {
    /**
     * @var string
     */
    private $testDbName = 'imboMongoDBIntegrationTestDB';

    /**
     * @var string
     */
    private $testCollectionName = 'imboMongoDBIntegrationTestCollection';

    /**
     * @see Imbo\IntegrationTest\Database\DatabaseTests::getDriver()
     */
    protected function getDriver() {
        return new MongoDB(array(
            'databaseName' => $this->testDbName,
            'collectionName' => $this->testCollectionName
        ));
    }

    /**
     * Make sure we have the mongo extension available and drop the test database just in case
     */
    public function setUp() {
        if (!extension_loaded('mongo')) {
            $this->markTestSkipped('pecl/mongo is required to run this test');
        }

        $mongo = new Mongo();
        $mongo->selectDB($this->testDbName)->drop();

        parent::setUp();
    }

    /**
     * Drop the test database after each test
     */
    public function tearDown() {
        if (extension_loaded('mongo')) {
            $mongo = new Mongo();
            $mongo->selectDB($this->testDbName)->drop();
        }

        parent::tearDown();
    }

    /**
     * @covers Imbo\Database\MongoDB::getMongo
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionMessage Could not connect to database
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenUSingAnInvalidMongodbHostname() {
        $db = new MongoDB(array(
            'server' => 'foobar',
        ));
        $db->getStatus();
    }
}
