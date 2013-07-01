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
    MongoClient;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Integration tests
 */
class MongoDBTest extends DatabaseTests {
    /**
     * @see Imbo\IntegrationTest\Database\DatabaseTests::getDriver()
     */
    protected function getDriver() {
        return new MongoDB(array(
            'databaseName' => 'imboIntegrationTestDatabase',
        ));
    }

    /**
     * Make sure we have the mongo extension available and drop the test database just in case
     */
    public function setUp() {
        if (!extension_loaded('mongo') || !class_exists('MongoClient')) {
            $this->markTestSkipped('pecl/mongo >= 1.3.0 is required to run this test');
        }

        $client = new MongoClient();
        $client->selectDB('imboIntegrationTestDatabase')->drop();

        parent::setUp();
    }

    /**
     * Drop the test database after each test
     */
    public function tearDown() {
        if (extension_loaded('mongo') && class_exists('MongoClient')) {
            $client = new MongoClient();
            $client->selectDB('imboIntegrationTestDatabase')->drop();
        }

        parent::tearDown();
    }

    /**
     * @covers Imbo\Database\MongoDB::getStatus
     */
    public function testReturnsFalseWhenFetchingStatusAndTheHostnameIsNotCorrect() {
        $db = new MongoDB(array(
            'server' => 'foobar',
        ));
        $this->assertFalse($db->getStatus());
    }
}
