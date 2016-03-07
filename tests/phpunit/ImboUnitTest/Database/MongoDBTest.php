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
    MongoDB\Driver\Manager as DriverManager,
    MongoDB\Collection,
    MongoDB\Driver\Exception\Exception as MongoException,
    MongoDB\Driver\Exception\RuntimeException as RuntimeException,
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
     * @var MongoDB\Driver\Manager
     */
    private $driverManager;

    /**
     * @var MongoDB\Collection
     */
    private $imageCollection;

    /**
     * @var MongoDB\Collection
     */
    private $shortUrlCollection;

    /**
     * Set up the mongo and collection mocks and the driver that we want to test
     */
    public function setUp() {
        if (!class_exists('MongoDB\Driver\Manager')) {
            $this->markTestSkipped('pecl/mongodb >= 1.0.0 is required to run this test');
        }

        $this->driverManager = new DriverManager('mongodb://localhost:27017');
        $this->imageCollection = $this->getMockBuilder('MongoDB\Collection')->disableOriginalConstructor()->getMock();
        $this->shortUrlCollection = $this->getMockBuilder('MongoDB\Collection')->disableOriginalConstructor()->getMock();
        $this->driver = new MongoDB([], $this->driverManager, $this->imageCollection, $this->shortUrlCollection);
    }

    /**
     * Teardown the instances
     */
    public function tearDown() {
        $this->driverManager = null;
        $this->imageCollection = null;
        $this->shortUrlCollection = null;
        $this->driver = null;
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
                              ->method('insertOne')
                              ->will($this->throwException(new RuntimeException()));

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
                              ->method('updateOne')
                              ->will($this->throwException(new RuntimeException()));

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
                              ->will($this->throwException(new RuntimeException()));

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
                              ->method('updateOne')
                              ->will($this->throwException(new RuntimeException()));

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
                              ->will($this->throwException(new RuntimeException()));

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
                              ->will($this->throwException(new RuntimeException()));

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
                              ->will($this->throwException(new RuntimeException()));

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
                              ->will($this->throwException(new RuntimeException()));

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
                              ->method('findOne')
                              ->will($this->throwException(new RuntimeException()));

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
                              ->will($this->throwException(new RuntimeException()));

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
                              ->will($this->throwException(new RuntimeException()));

        $this->driver->getImageMimeType('key', 'identifier');
    }
}
