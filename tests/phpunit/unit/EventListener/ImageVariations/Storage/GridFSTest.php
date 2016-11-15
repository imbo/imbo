<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\EventListener\ImageVariations\Storage;

use Imbo\EventListener\ImageVariations\Storage\GridFS,
    MongoException;

/**
 * @covers Imbo\EventListener\ImageVariations\Storage\GridFS
 * @group unit
 * @group storage
 * @group mongodb
 */
class GridFSTest extends \PHPUnit_Framework_TestCase {
    private $databaseName = 'imboGridFSUnitTest';

    protected function setUp() {
        if (!class_exists('MongoClient')) {
            $this->markTestSkipped('pecl/mongo >= 1.3.0 is required to run this test');
        }
    }

    /**
     * @covers Imbo\EventListener\ImageVariations\Storage\GridFS::__construct
     * @covers Imbo\EventListener\ImageVariations\Storage\GridFS::getGrid
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionMessage Could not connect to database
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenNotAbleToGetDatabase() {
        $client = $this->createMock('MongoClient');

        $client
            ->expects($this->once())
            ->method('selectDB')
            ->will($this->throwException(new MongoException()));

        $adapter = new GridFS([
            'databaseName' => $this->databaseName,
        ], $client);

        $adapter->storeImageVariation('key', 'id', 'blob', 700);
    }

    /**
     * @covers Imbo\EventListener\ImageVariations\Storage\GridFS::__construct
     * @covers Imbo\EventListener\ImageVariations\Storage\GridFS::getGrid
     */
    public function testCanPassGridInstance() {
        $client = $this->createMock('MongoClient');
        $grid = $this->createMock('MongoGridFS');

        $grid
            ->expects($this->once())
            ->method('storeBytes')
            ->will($this->returnValue(true));

        $adapter = new GridFS([
            'databaseName' => $this->databaseName,
        ], $client, $grid);

        $adapter->storeImageVariation('key', 'id', 'blob', 700);
    }
}
