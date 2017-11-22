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

use Imbo\Storage\GridFS;
use MongoDB\Client;

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
    private $databaseName = 'imboGridFSIntegrationTest';

    /**
     * @see ImboIntegrationTest\Storage\StorageTests::getDriver()
     */
    protected function getDriver() {
        return new GridFS([
            'databaseName' => $this->databaseName,
        ]);
    }

    public function setUp() {
        if (!class_exists('MongoDB\Driver\Manager') || !class_exists('MongoDB\Client')) {
            $this->markTestSkipped('pecl/mongodb and mongodb/mongodb are both required to run this test');
        }

        (new Client())->dropDatabase($this->databaseName);

        parent::setUp();
    }

    public function tearDown() {
        if (class_exists('MongoDB\Client')) {
            (new Client())->dropDatabase($this->databaseName);
        }

        parent::tearDown();
    }

    /**
     * @covers Imbo\Storage\GridFS::getStatus
     */
    public function testReturnsFalseWhenFetchingStatusAndTheHostnameIsNotCorrect() {
        $storage = new GridFS([
            'uri' => 'mongodb://localhost:1',
        ]);
        $this->assertFalse($storage->getStatus());
    }
}
