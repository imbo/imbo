<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboIntegrationTest\EventListener\ImageVariations\Storage;

use Imbo\EventListener\ImageVariations\Storage\GridFS,
    MongoClient,
    MongoException;

/**
 * @covers Imbo\EventListener\ImageVariations\Storage\GridFS
 * @group integration
 * @group storage
 * @group mongodb
 */
class GridFSTest extends StorageTests {
    private $databaseName = 'imboGridFSIntegrationTest';

    /**
     * @see ImboIntegrationTest\EventListener\ImageVariations\Storage\StorageTests::getAdapter()
     */
    protected function getAdapter() {
        return new GridFS([
            'databaseName' => $this->databaseName,
        ]);
    }

    /**
     * Make sure we have the mongo extension available and drop the test database just in case
     */
    public function setUp() {
        if (!extension_loaded('mongo') || !class_exists('MongoClient')) {
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
        if (extension_loaded('mongo') && class_exists('MongoClient')) {
            $client = new MongoClient();
            $client->selectDB($this->databaseName)->drop();
        }

        parent::tearDown();
    }
}
