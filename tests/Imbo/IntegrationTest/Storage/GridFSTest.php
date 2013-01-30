<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\IntegrationTest\Storage;

use Imbo\Storage\GridFS,
    Mongo;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Integration tests
 */
class GridFSTest extends StorageTests {
    /**
     * Name of the test db
     *
     * @var string
     */
    private $testDbName = 'imboGridFSIntegrationTest';

    /**
     * @see Imbo\IntegrationTest\Storage\StorageTests::getDriver()
     */
    protected function getDriver() {
        return new GridFS(array(
            'databaseName' => $this->testDbName,
        ));
    }

    public function setUp() {
        if (!extension_loaded('mongo')) {
            $this->markTestSkipped('pecl/mongo is required to run this test');
        }

        $mongo = new Mongo();
        $mongo->selectDB($this->testDbName)->drop();

        parent::setUp();
    }

    public function tearDown() {
        if (extension_loaded('mongo')) {
            $mongo = new Mongo();
            $mongo->selectDB($this->testDbName)->drop();
        }

        parent::tearDown();
    }

    /**
     * @covers Imbo\Storage\GridFS::getStatus
     */
    public function testReturnsFalseWhenFetchingStatusAndTheHostnameIsNotCorrect() {
        $storage = new GridFS(array(
            'server' => 'foobar',
        ));
        $this->assertFalse($storage->getStatus());
    }
}
