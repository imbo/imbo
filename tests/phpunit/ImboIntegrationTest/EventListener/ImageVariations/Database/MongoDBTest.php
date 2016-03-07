<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboIntegrationTest\EventListener\ImageVariations\Database;

use Imbo\EventListener\ImageVariations\Database\MongoDB,
    MongoDB\Driver\Manager,
    MongoDB\Database;

/**
 * @covers Imbo\EventListener\ImageVariations\Database\MongoDB
 * @group integration
 * @group database
 * @group mongodb
 */
class MongoDBTest extends DatabaseTests {
    private $databaseName = 'imboIntegrationTestDatabase';

    /**
     * @see ImboIntegrationTest\EventListener\ImageVariations\Database\DatabaseTests::getAdapter()
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
        if (!class_exists('MongoDB\Driver\Manager')) {
            $this->markTestSkipped('pecl/mongodb >= 1.0.0 is required to run this test');
        }

        $this->dropDatabase();

        parent::setUp();
    }

    /**
     * Drop the test database after each test
     */
    public function tearDown() {
        if (class_exists('MongoDB\Driver\Manager')) {
            $this->dropDatabase();
        }

        parent::tearDown();
    }

    private function dropDatabase() {
        (new Database(new Manager('mongodb://localhost:27017'), $this->databaseName))->drop();
    }
}
