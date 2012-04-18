<?php
/**
 * Imbo
 *
 * Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * * The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\UnitTest\Storage;

use Imbo\Storage\GridFS,
    DateTime,
    MongoGridFSFile;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\Storage\GridFS
 */
class GridFSTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Imbo\Storage\GridFS
     */
    private $driver;

    /**
     * @var MongoGridFS
     */
    private $grid;

    /**
     * Public key that can be used in tests
     *
     * @var string
     */
    private $publicKey = 'key';

    /**
     * Image identifier that can be used in tests
     *
     * @var string
     */
    private $imageIdentifier = '96d08a5943ebf1c5635a2995c9408cdd';

    /**
     * Setup method
     */
    public function setUp() {
        $this->grid = $this->getMockBuilder('MongoGridFS')->disableOriginalConstructor()->getMock();
        $this->driver = new GridFS(array(), null, $this->grid);
    }

    /**
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionCode 400
     * @covers Imbo\Storage\GridFS::store
     * @covers Imbo\Storage\GridFS::imageExists
     */
    public function testStoreWhenImageExists() {
        $file = $this->getMockBuilder('MongoGridFSFile')->disableOriginalConstructor()->getMock();

        $cursor = $this->getMockBuilder('MongoGridFSCursor')->disableOriginalConstructor()->getMock();
        $cursor->expects($this->once())->method('count')->will($this->returnValue(1));
        $cursor->expects($this->once())->method('getNext')->will($this->returnValue($file));

        $this->grid->expects($this->once())->method('find')->will($this->returnValue($cursor));

        $this->driver->store($this->publicKey, $this->imageIdentifier, $this->getMock('Imbo\Image\ImageInterface'));
    }

    /**
     * @covers Imbo\Storage\GridFS::store
     * @covers Imbo\Storage\GridFS::imageExists
     */
    public function testStore() {
        $data = 'some content';

        $cursor = $this->getMockBuilder('MongoGridFSCursor')->disableOriginalConstructor()->getMock();
        $cursor->expects($this->once())->method('count')->will($this->returnValue(0));

        $this->grid->expects($this->once())->method('find')->will($this->returnValue($cursor));
        $this->grid->expects($this->once())->method('storeBytes')->with($data, $this->isType('array'), $this->isType('array'))->will($this->returnValue($cursor));

        $this->assertTrue($this->driver->store($this->publicKey, $this->imageIdentifier, $data));
    }

    /**
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionCode 500
     * @covers Imbo\Storage\GridFS::store
     * @covers Imbo\Storage\GridFS::imageExists
     */
    public function testStoreWhenMongoThrowsException() {
        $data = 'some content';

        $cursor = $this->getMockBuilder('MongoGridFSCursor')->disableOriginalConstructor()->getMock();
        $cursor->expects($this->once())->method('count')->will($this->returnValue(0));

        $this->grid->expects($this->once())->method('find')->will($this->returnValue($cursor));
        $this->grid->expects($this->once())->method('storeBytes')->will($this->throwException($this->getMock('MongoCursorException')));

        $this->assertTrue($this->driver->store($this->publicKey, $this->imageIdentifier, $data));
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

        $this->driver->delete($this->publicKey, $this->imageIdentifier);
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

        $this->driver->delete($this->publicKey, $this->imageIdentifier);
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

        $this->driver->getImage($this->publicKey, $this->imageIdentifier);
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

        $this->assertSame($data, $this->driver->getImage($this->publicKey, $this->imageIdentifier));
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

        $this->driver->getLastModified($this->publicKey, $this->imageIdentifier);
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

        $this->assertInstanceOf('DateTime', ($date = $this->driver->getLastModified($this->publicKey, $this->imageIdentifier)));
        $this->assertSame(1334579830, $date->getTimestamp());

    }
}

class TestFile extends MongoGridFSFile {
    public $file = array(
        'created' => 1334579830,
    );

    public function __construct() {}
}
