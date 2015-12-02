<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Storage;

use Imbo\Storage\GridFS,
    DateTime,
    MongoClient,
    MongoGridFS,
    MongoGridFSFile;

/**
 * @covers Imbo\Storage\GridFS
 * @group unit
 * @group storage
 * @group mongodb
 */
class GridFSTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var GridFS
     */
    private $driver;

    /**
     * @var MongoGridFS
     */
    private $grid;

    /**
     * @var MongoClient
     */
    private $mongoClient;

    /**
     * User that can be used in tests
     *
     * @var string
     */
    private $user = 'user';

    /**
     * Image identifier that can be used in tests
     *
     * @var string
     */
    private $imageIdentifier = '96d08a5943ebf1c5635a2995c9408cdd';

    /**
     * Set up the driver
     */
    public function setUp() {
        if (!class_exists('MongoClient')) {
            $this->markTestSkipped('pecl/mongo >= 1.3.0 is required to run this test');
        }

        $this->grid = $this->getMockBuilder('MongoGridFS')->disableOriginalConstructor()->getMock();
        $this->mongoClient = $this->getMockBuilder('MongoClient')->disableOriginalConstructor()->getMock();
        $this->driver = new GridFS([], $this->mongoClient, $this->grid);
    }

    /**
     * Tear down the driver
     */
    public function tearDown() {
        $this->grid = null;
        $this->mongoClient = null;
        $this->driver = null;
    }

    /**
     * @covers Imbo\Storage\GridFS::store
     * @covers Imbo\Storage\GridFS::imageExists
     */
    public function testStore() {
        $data = 'some content';

        $cursor = $this->getMockBuilder('MongoGridFSCursor')->disableOriginalConstructor()->getMock();
        $cursor->expects($this->once())->method('count')->will($this->returnValue(0));

        $this->grid->expects($this->at(0))
                   ->method('find')
                   ->with([
                       'user' => $this->user,
                       'imageIdentifier' => $this->imageIdentifier
                   ])
                   ->will($this->returnValue($cursor));
        $this->grid->expects($this->once())->method('storeBytes')->with($data, $this->isType('array'));

        $this->assertTrue($this->driver->store($this->user, $this->imageIdentifier, $data));
    }

    /**
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionCode 404
     * @covers Imbo\Storage\GridFS::delete
     * @covers Imbo\Storage\GridFS::imageExists
     */
    public function testDeleteFileThatDoesNotExist() {
        $cursor = $this->getMockBuilder('MongoGridFSCursor')->disableOriginalConstructor()->getMock();
        $cursor->expects($this->once())->method('count')->will($this->returnValue(0));

        $this->grid->expects($this->once())->method('find')->will($this->returnValue($cursor));

        $this->driver->delete($this->user, $this->imageIdentifier);
    }

    /**
     * @covers Imbo\Storage\GridFS::delete
     * @covers Imbo\Storage\GridFS::imageExists
     */
    public function testDeleteFile() {
        $file = $this->getMockBuilder('MongoGridFSFile')->disableOriginalConstructor()->getMock();

        $cursor = $this->getMockBuilder('MongoGridFSCursor')->disableOriginalConstructor()->getMock();
        $cursor->expects($this->once())->method('count')->will($this->returnValue(1));
        $cursor->expects($this->once())->method('getNext')->will($this->returnValue($file));

        $this->grid->expects($this->once())->method('find')->will($this->returnValue($cursor));
        $this->grid->expects($this->once())->method('delete');

        $this->driver->delete($this->user, $this->imageIdentifier);
    }

    /**
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionCode 404
     * @covers Imbo\Storage\GridFS::getImage
     * @covers Imbo\Storage\GridFS::imageExists
     */
    public function testGetImageThatDoesNotExist() {
        $cursor = $this->getMockBuilder('MongoGridFSCursor')->disableOriginalConstructor()->getMock();
        $cursor->expects($this->once())->method('count')->will($this->returnValue(0));

        $this->grid->expects($this->once())->method('find')->will($this->returnValue($cursor));

        $this->driver->getImage($this->user, $this->imageIdentifier);
    }

    /**
     * @covers Imbo\Storage\GridFS::getImage
     * @covers Imbo\Storage\GridFS::imageExists
     */
    public function testGetImage() {
        $data = 'file contents';

        $file = $this->getMockBuilder('MongoGridFSFile')->disableOriginalConstructor()->getMock();
        $file->expects($this->once())->method('getBytes')->will($this->returnValue($data));

        $cursor = $this->getMockBuilder('MongoGridFSCursor')->disableOriginalConstructor()->getMock();
        $cursor->expects($this->once())->method('count')->will($this->returnValue(1));
        $cursor->expects($this->once())->method('getNext')->will($this->returnValue($file));

        $this->grid->expects($this->once())->method('find')->will($this->returnValue($cursor));

        $this->assertSame($data, $this->driver->getImage($this->user, $this->imageIdentifier));
    }

    /**
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionCode 404
     * @covers Imbo\Storage\GridFS::getLastModified
     * @covers Imbo\Storage\GridFS::imageExists
     */
    public function testGetLastModifiedWhenImageDoesNotExist() {
        $cursor = $this->getMockBuilder('MongoGridFSCursor')->disableOriginalConstructor()->getMock();
        $cursor->expects($this->once())->method('count')->will($this->returnValue(0));

        $this->grid->expects($this->once())->method('find')->will($this->returnValue($cursor));

        $this->driver->getLastModified($this->user, $this->imageIdentifier);
    }

    /**
     * @covers Imbo\Storage\GridFS::getLastModified
     * @covers Imbo\Storage\GridFS::imageExists
     */
    public function testGetLastModified() {
        $file = new TestFile();

        $cursor = $this->getMockBuilder('MongoGridFSCursor')->disableOriginalConstructor()->getMock();
        $cursor->expects($this->once())->method('count')->will($this->returnValue(1));
        $cursor->expects($this->once())->method('getNext')->will($this->returnValue($file));

        $this->grid->expects($this->once())->method('find')->will($this->returnValue($cursor));

        $this->assertInstanceOf('DateTime', ($date = $this->driver->getLastModified($this->user, $this->imageIdentifier)));
        $this->assertSame(1334579830, $date->getTimestamp());

    }

    /**
     * @covers Imbo\Storage\GridFS::getStatus
     */
    public function testGetStatusWhenMongoIsNotConnectable() {
        $this->mongoClient->expects($this->once())->method('connect')->will($this->returnValue(false));
        $this->assertFalse($this->driver->getStatus());
    }

    /**
     * @covers Imbo\Storage\GridFS::getStatus
     */
    public function testGetStatusWhenMongoIsConnectable() {
        $this->mongoClient->expects($this->once())->method('connect')->will($this->returnValue(true));
        $this->assertTrue($this->driver->getStatus());
    }
}

if (class_exists('MongoGridFSFile')) {
    class TestFile extends MongoGridFSFile {
        public $file = [
            'updated' => 1334579830,
        ];

        public function __construct() {}
    }
}
