<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\EventListener\ImageVariations\Database;

use Imbo\EventListener\ImageVariations\Database\MongoDB,
    MongoException,
    MongoClient;

/**
 * @covers Imbo\EventListener\ImageVariations\Database\MongoDB
 * @group unit
 * @group database
 * @group mongodb
 */
class MongoDBTest extends \PHPUnit_Framework_TestCase {
    private $databaseName = 'imboUnitTestDatabase';

    protected function setUp() {
        if (!class_exists('MongoClient')) {
            $this->markTestSkipped('pecl/mongo >= 1.3.0 is required to run this test');
        }
    }

    /**
     * @covers Imbo\EventListener\ImageVariations\Database\MongoDB::__construct
     * @covers Imbo\EventListener\ImageVariations\Database\MongoDB::storeImageVariationMetadata
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionMessage Unable to save image variation data
     * @expectedExceptionCode 500
     */
    public function testInsertFailureThrowsDatabaseException() {
        $client = $this->getMockBuilder('MongoClient')->disableOriginalConstructor()->getMock();
        $collection = $this->getMockBuilder('MongoCollection')->disableOriginalConstructor()->getMock();

        $collection
            ->expects($this->once())
            ->method('insert')
            ->will($this->throwException(new MongoException()));

        $adapter = new MongoDB([
            'databaseName' => $this->databaseName,
        ], $client, $collection);

        $adapter->storeImageVariationMetadata('key', 'id', 700, 700);
    }

    /**
     * @covers Imbo\EventListener\ImageVariations\Database\MongoDB::__construct
     * @covers Imbo\EventListener\ImageVariations\Database\MongoDB::getCollection
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionMessage Could not select collection
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenNotAbleToGetCollection() {
        $client = $this->getMockBuilder('MongoClient')->disableOriginalConstructor()->getMock();

        $client
            ->expects($this->once())
            ->method('selectCollection')
            ->will($this->throwException(new MongoException()));

        $adapter = new MongoDB([
            'databaseName' => $this->databaseName,
        ], $client);

        $adapter->storeImageVariationMetadata('key', 'id', 700, 700);
    }
}
