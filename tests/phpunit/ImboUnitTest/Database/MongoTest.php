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

use Imbo\Database\Mongo,
    MongoDB\Client,
    MongoDB\Collection,
    MongoDB\Exception\InvalidArgumentException,
    ReflectionMethod;

/**
 * @covers Imbo\Database\Mongo
 * @group unit
 * @group database
 * @group mongo
 */
class MongoTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Mongo
     */
    private $driver;

    /**
     * @var Mongo
     */
    private $driverMock;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var Collection
     */
    private $imageCollection;

    /**
     * @var Collection
     */
    private $shortUrlCollection;

    /**
     * Set up the mongo and collection mocks and the driver that we want to test
     */
    public function setUp() {
        if (!class_exists('MongoDB\Driver\Manager')) {
            $this->markTestSkipped('pecl/mongodb >= 1.1.2 is required to run this test');
        }

        $this->client = $this->getMockBuilder('\MongoDB\Client')->disableOriginalConstructor()->getMock();
        $this->imageCollection = $this->getMockBuilder('\MongoDB\Collection')->disableOriginalConstructor()->getMock();
        $this->shortUrlCollection = $this->getMockBuilder('\MongoDB\Collection')->disableOriginalConstructor()->getMock();
        $this->driver = new Mongo([], $this->client, $this->imageCollection, $this->shortUrlCollection);
        $this->driverMock = $this->getMockBuilder('\Imbo\Database\Mongo')
                                ->disableOriginalConstructor()
                                ->setMethods(['getStatus'])
                                ->getMockForAbstractClass();
    }

    /**
     * Teardown the instances
     */
    public function tearDown() {
        $this->client = null;
        $this->imageCollection = null;
        $this->shortUrlCollection = null;
        $this->driver = null;
        $this->driverMock = null;
    }

    /**
     * @covers Imbo\Database\Mongo::getStatus
     */
    public function testGetStatusWhenMongoIsNotConnectable() {
        $this->driverMock->expects($this->once())->method('getStatus')->will($this->returnValue(false));

        $this->assertFalse($this->driverMock->getStatus());
    }

    /**
     * @covers Imbo\Database\Mongo::getStatus
     */
    public function testGetStatusWhenMongoIsConnectable() {
        $this->driverMock->expects($this->once())->method('getStatus')->will($this->returnValue(true));

        $this->assertTrue($this->driverMock->getStatus());
    }

    /**
     * @covers Imbo\Database\Mongo::insertImage
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
                              ->will($this->throwException(new InvalidArgumentException()));

        $this->driver->insertImage('key', 'identifier', $this->getMock('Imbo\Model\Image'));
    }

    /**
     * @covers Imbo\Database\Mongo::insertImage
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
                              ->will($this->throwException(new InvalidArgumentException()));

        $this->driver->insertImage('key', 'identifier', $this->getMock('Imbo\Model\Image'));
    }

    /**
     * @covers Imbo\Database\Mongo::deleteImage
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionMessage Unable to delete image data
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenMongoFailsDuringDeleteImage() {
        $this->imageCollection->expects($this->once())
                              ->method('findOne')
                              ->will($this->throwException(new InvalidArgumentException()));

        $this->driver->deleteImage('key', 'identifier');
    }

    /**
     * @covers Imbo\Database\Mongo::updateMetadata
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
                              ->will($this->throwException(new InvalidArgumentException()));

        $this->driver->updateMetadata('key', 'identifier', ['key' => 'value']);
    }

    /**
     * @covers Imbo\Database\Mongo::getMetadata
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionMessage Unable to fetch meta data
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenMongoFailsDuringGetMetadata() {
        $this->imageCollection->expects($this->once())
                              ->method('findOne')
                              ->will($this->throwException(new InvalidArgumentException()));

        $this->driver->getMetadata('key', 'identifier');
    }

    /**
     * @covers Imbo\Database\Mongo::deleteMetadata
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionMessage Unable to delete meta data
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenMongoFailsDuringDeleteMetadata() {
        $this->imageCollection->expects($this->once())
                              ->method('findOne')
                              ->will($this->throwException(new InvalidArgumentException()));

        $this->driver->deleteMetadata('key', 'identifier');
    }

    /**
     * @covers Imbo\Database\Mongo::getImages
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionMessage Unable to search for images
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenMongoFailsDuringGetImages() {
        $this->imageCollection->expects($this->once())
                              ->method('find')
                              ->will($this->throwException(new InvalidArgumentException()));

        $this->driver->getImages(['key'], $this->getMock('Imbo\Resource\Images\Query'), $this->getMock('Imbo\Model\Images'));
    }

    /**
     * @covers Imbo\Database\Mongo::load
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionMessage Unable to fetch image data
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenMongoFailsDuringLoad() {
        $this->imageCollection->expects($this->once())
                              ->method('findOne')
                              ->will($this->throwException(new InvalidArgumentException()));

        $this->driver->load('key', 'identifier', $this->getMock('Imbo\Model\Image'));
    }

    /**
     * @covers Imbo\Database\Mongo::getLastModified
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionMessage Unable to fetch image data
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenMongoFailsDuringGetLastModified() {
        $this->imageCollection->expects($this->once())
                              ->method('findOne')
                              ->will($this->throwException(new InvalidArgumentException()));

        $this->driver->getLastModified(['key']);
    }

    /**
     * @covers Imbo\Database\Mongo::getNumImages
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionMessage Unable to fetch information from the database
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenMongoFailsDuringGetNumImages() {
        $this->imageCollection->expects($this->once())
                              ->method('count')
                              ->will($this->throwException(new InvalidArgumentException()));

        $this->driver->getNumImages('key');
    }

    /**
     * @covers Imbo\Database\Mongo::getImageMimeType
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionMessage Unable to fetch image meta data
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenMongoFailsDuringGetImageMimeType() {
        $this->imageCollection->expects($this->once())
                              ->method('findOne')
                              ->will($this->throwException(new InvalidArgumentException()));

        $this->driver->getImageMimeType('key', 'identifier');
    }

    /**
     * @covers Imbo\Database\Mongo::getCollection
     * @expectedException Imbo\Exception\DatabaseException
     * @expectedExceptionMessage Could not select collection
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenNotAbleToGetCollection() {
        $driver = new Mongo([], $this->client);

        $this->client->expects($this->once())
                          ->method('selectCollection')
                          ->will($this->throwException(new InvalidArgumentException()));

        $method = new ReflectionMethod('Imbo\Database\Mongo', 'getCollection');
        $method->setAccessible(true);
        $method->invoke($driver, 'image');
    }
}
