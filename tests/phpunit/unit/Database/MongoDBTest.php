<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Database;

use Imbo\Database\MongoDB,
    MongoException,
    ReflectionMethod;

/**
 * @covers Imbo\Database\MongoDB
 * @group unit
 * @group database
 * @group mongodb
 */
class MongoDBTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var MongoDB
     */
    private $driver;

    /**
     * @var MongoClient
     */
    private $mongoClient;

    /**
     * @var MongoCollection
     */
    private $imageCollection;

    /**
     * @var MongoCollection
     */
    private $shortUrlCollection;

    /**
     * Set up the mongo and collection mocks and the driver that we want to test
     */
    public function setUp() {
        if (!class_exists('MongoClient')) {
            $this->markTestSkipped('pecl/mongo >= 1.3.0 is required to run this test');
        }

        $this->mongoClient = $this->getMockBuilder('MongoClient')->disableOriginalConstructor()->getMock();
        $this->imageCollection = $this->getMockBuilder('MongoCollection')->disableOriginalConstructor()->getMock();
        $this->shortUrlCollection = $this->getMockBuilder('MongoCollection')->disableOriginalConstructor()->getMock();
        $this->driver = new MongoDB([], $this->mongoClient, $this->imageCollection, $this->shortUrlCollection);
    }

    /**
     * Teardown the instances
     */
    public function tearDown() {
        $this->mongoClient = null;
        $this->imageCollection = null;
        $this->shortUrlCollection = null;
        $this->driver = null;
    }

    /**
     * @covers Imbo\Database\MongoDB::getStatus
     */
    public function testGetStatusWhenMongoIsNotConnectable() {
        $this->mongoClient->expects($this->once())->method('connect')->will($this->returnValue(false));
        $this->assertFalse($this->driver->getStatus());
    }

    /**
     * @covers Imbo\Database\MongoDB::getStatus
     */
    public function testGetStatusWhenMongoIsConnectable() {
        $this->mongoClient->expects($this->once())->method('connect')->will($this->returnValue(true));
        $this->assertTrue($this->driver->getStatus());
    }

    /**
     * @covers Imbo\Database\MongoDB::insertImage
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionMessage Unable to save image data
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenMongoFailsDuringInsertImage() {
        $this->imageCollection->expects($this->once())
                              ->method('findOne')
                              ->will($this->returnValue(null));
        $this->imageCollection->expects($this->once())
                              ->method('insert')
                              ->will($this->throwException(new MongoException()));

        $this->driver->insertImage('key', 'identifier', $this->getMock('Imbo\Model\Image'));
    }

    /**
     * @covers Imbo\Database\MongoDB::insertImage
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionMessage Unable to save image data
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenMongoFailsDuringInsertImageAndImageAlreadyExists() {
        $this->imageCollection->expects($this->once())
                              ->method('findOne')
                              ->will($this->returnValue(['some' => 'data']));
        $this->imageCollection->expects($this->once())
                              ->method('update')
                              ->will($this->throwException(new MongoException()));

        $this->driver->insertImage('key', 'identifier', $this->getMock('Imbo\Model\Image'));
    }

    /**
     * @covers Imbo\Database\MongoDB::deleteImage
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionMessage Unable to delete image data
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenMongoFailsDuringDeleteImage() {
        $this->imageCollection->expects($this->once())
                              ->method('findOne')
                              ->will($this->throwException(new MongoException()));

        $this->driver->deleteImage('key', 'identifier');
    }

    /**
     * @covers Imbo\Database\MongoDB::updateMetadata
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionMessage Unable to update meta data
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenMongoFailsDuringUpdateMetadata() {
        $this->imageCollection->expects($this->once())
                              ->method('findOne')
                              ->will($this->returnValue(['some' => 'data']));

        $this->imageCollection->expects($this->once())
                              ->method('update')
                              ->will($this->throwException(new MongoException()));

        $this->driver->updateMetadata('key', 'identifier', ['key' => 'value']);
    }

    /**
     * @covers Imbo\Database\MongoDB::getMetadata
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionMessage Unable to fetch meta data
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenMongoFailsDuringGetMetadata() {
        $this->imageCollection->expects($this->once())
                              ->method('findOne')
                              ->will($this->throwException(new MongoException()));

        $this->driver->getMetadata('key', 'identifier');
    }

    /**
     * @covers Imbo\Database\MongoDB::deleteMetadata
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionMessage Unable to delete meta data
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenMongoFailsDuringDeleteMetadata() {
        $this->imageCollection->expects($this->once())
                              ->method('findOne')
                              ->will($this->throwException(new MongoException()));

        $this->driver->deleteMetadata('key', 'identifier');
    }

    /**
     * @covers Imbo\Database\MongoDB::getImages
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionMessage Unable to search for images
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenMongoFailsDuringGetImages() {
        $this->imageCollection->expects($this->once())
                              ->method('find')
                              ->will($this->throwException(new MongoException()));

        $this->driver->getImages(['key'], $this->getMock('Imbo\Resource\Images\Query'), $this->getMock('Imbo\Model\Images'));
    }

    /**
     * @covers Imbo\Database\MongoDB::load
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionMessage Unable to fetch image data
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenMongoFailsDuringLoad() {
        $this->imageCollection->expects($this->once())
                              ->method('findOne')
                              ->will($this->throwException(new MongoException()));

        $this->driver->load('key', 'identifier', $this->getMock('Imbo\Model\Image'));
    }

    /**
     * @covers Imbo\Database\MongoDB::getLastModified
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionMessage Unable to fetch image data
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenMongoFailsDuringGetLastModified() {
        $this->imageCollection->expects($this->once())
                              ->method('find')
                              ->will($this->throwException(new MongoException()));

        $this->driver->getLastModified(['key']);
    }

    /**
     * @covers Imbo\Database\MongoDB::getNumImages
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionMessage Unable to fetch information from the database
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenMongoFailsDuringGetNumImages() {
        $this->imageCollection->expects($this->once())
                              ->method('count')
                              ->will($this->throwException(new MongoException()));

        $this->driver->getNumImages('key');
    }

    /**
     * @covers Imbo\Database\MongoDB::getImageMimeType
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionMessage Unable to fetch image meta data
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenMongoFailsDuringGetImageMimeType() {
        $this->imageCollection->expects($this->once())
                              ->method('findOne')
                              ->will($this->throwException(new MongoException()));

        $this->driver->getImageMimeType('key', 'identifier');
    }

    /**
     * @covers Imbo\Database\MongoDB::getCollection
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionMessage Could not select collection
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenNotAbleToGetCollection() {
        $driver = new MongoDB([], $this->mongoClient);

        $this->mongoClient->expects($this->once())
                          ->method('selectCollection')
                          ->will($this->throwException(new MongoException()));

        $method = new ReflectionMethod('Imbo\Database\MongoDB', 'getCollection');
        $method->setAccessible(true);
        $method->invoke($driver, 'image');
    }
}
