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
 * @package Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\Storage;

use vfsStream,
    vfsStreamWrapper;

/**
 * @package Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\Storage\Filesystem
 */
class FilesystemTest extends \PHPUnit_Framework_TestCase {
    /**
     * Public key that can be used in tests
     *
     * @var string
     */
    private $publicKey = '59632bc7a908b9cd47a35d03fc992aa7';

    /**
     * Image identifier that can be used in tests
     *
     * @var string
     */
    private $imageIdentifier = '96d08a5943ebf1c5635a2995c9408cdd.png';

    /**
     * Setup method
     */
    public function setUp() {
        if (!class_exists('vfsStream', true)) {
            $this->markTestSkipped('This testcase requires vfsStream to run');
        }
    }

    /**
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionMessage File not found
     * @covers Imbo\Storage\Filesystem::delete
     */
    public function testDeleteFileThatDoesNotExist() {
        $driver = new Filesystem(array('dataDir' => 'foobar'));
        $driver->delete($this->publicKey, $this->imageIdentifier);
    }

    /**
     * @covers Imbo\Storage\Filesystem::delete
     */
    public function testDelete() {
        vfsStream::setup('basedir');
        $driver = new Filesystem(array('dataDir' => vfsStream::url('basedir')));

        $root = vfsStreamWrapper::getRoot();
        $last = $root;

        $parts = array(
            $this->publicKey[0],
            $this->publicKey[1],
            $this->publicKey[2],
            $this->publicKey,
            $this->imageIdentifier[0],
            $this->imageIdentifier[1],
            $this->imageIdentifier[2],
        );

        foreach ($parts as $part) {
            $d = vfsStream::newDirectory($part);
            $last->addChild($d);
            $last = $d;
        }

        $last->addChild(vfsStream::newFile($this->imageIdentifier));

        $this->assertTrue($last->hasChild($this->imageIdentifier));
        $driver->delete($this->publicKey, $this->imageIdentifier);
        $this->assertFalse($last->hasChild($this->imageIdentifier));
    }

    /**
     * @expectedException Imbo\Exception\StorageException
     * @expectedExpectionMessage Could not store image
     * @covers Imbo\Storage\Filesystem::store
     */
    public function testStoreToUnwritablePath() {
        $image = $this->getMock('Imbo\Image\ImageInterface');
        $dir = 'unwritableDirectory';

        // Create the virtual directory with no permissions
        vfsStream::setup($dir, 0);

        $driver = new Filesystem(array('dataDir' => vfsStream::url($dir)));
        $driver->store($this->publicKey, $this->imageIdentifier, $image);
    }

    /**
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionMessage Image already exists
     * @expectedExceptionCode 400
     * @covers Imbo\Storage\Filesystem::store
     */
    public function testStoreFileTwice() {
        $content = 'some content';
        $image = $this->getMock('Imbo\Image\ImageInterface');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue($content));
        $baseDir = 'someDir';

        // Create the virtual directory
        vfsStream::setup($baseDir);

        $driver = new Filesystem(array('dataDir' => vfsStream::url($baseDir)));
        $this->assertTrue($driver->store($this->publicKey, $this->imageIdentifier, $image));
        $driver->store($this->publicKey, $this->imageIdentifier, $image);
    }

    /**
     * @covers Imbo\Storage\Filesystem::store
     */
    public function testStore() {
        $content = 'some content';
        $image = $this->getMock('Imbo\Image\ImageInterface');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue($content));
        $baseDir = 'someDir';

        // Create the virtual directory
        vfsStream::setup($baseDir);

        $driver = new Filesystem(array('dataDir' => vfsStream::url($baseDir)));
        $this->assertTrue($driver->store($this->publicKey, $this->imageIdentifier, $image));
    }

    /**
     * @covers Imbo\Storage\Filesystem::getImagePath
     */
    public function testGetImagePath() {
        $driver = new Filesystem(array('dataDir' => '/tmp'));
        $this->assertSame(
            '/tmp/5/9/6/59632bc7a908b9cd47a35d03fc992aa7/9/6/d/96d08a5943ebf1c5635a2995c9408cdd.png',
            $driver->getImagePath($this->publicKey, $this->imageIdentifier)
        );
        $this->assertSame(
            '/tmp/5/9/6/59632bc7a908b9cd47a35d03fc992aa7/9/6/d',
            $driver->getImagePath($this->publicKey, $this->imageIdentifier, false)
        );
    }

    /**
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionCode 404
     * @covers Imbo\Storage\Filesystem::load
     */
    public function testLoadFileThatDoesNotExist() {
        $image = $this->getMock('Imbo\Image\ImageInterface');
        $driver = new Filesystem(array('dataDir' => '/some/path'));
        $driver->load($this->publicKey, $this->imageIdentifier, $image);
    }

    /**
     * @covers Imbo\Storage\Filesystem::load
     */
    public function testLoad() {
        vfsStream::setup('basedir');
        $driver = new Filesystem(array('dataDir' => vfsStream::url('basedir')));

        $root = vfsStreamWrapper::getRoot();
        $last = $root;

        $parts = array(
            $this->publicKey[0],
            $this->publicKey[1],
            $this->publicKey[2],
            $this->publicKey,
            $this->imageIdentifier[0],
            $this->imageIdentifier[1],
            $this->imageIdentifier[2],
        );

        foreach ($parts as $part) {
            $d = vfsStream::newDirectory($part);
            $last->addChild($d);
            $last = $d;
        }

        $content = 'some binary content';
        $file = vfsStream::newFile($this->imageIdentifier);
        $file->setContent($content);
        $last->addChild($file);

        $image = $this->getMock('Imbo\Image\ImageInterface');
        $image->expects($this->once())->method('setBlob')->with($content);

        $this->assertTrue($driver->load($this->publicKey, $this->imageIdentifier, $image));
    }

    /**
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionMessage File not found
     * @expectedExceptionCode 404
     * @covers Imbo\Storage\Filesystem::getLastModified
     */
    public function testGetLastModifiedWithFileThatDoesNotExist() {
        $driver = new Filesystem(array('dataDir' => '/some/path'));
        $driver->getLastModified($this->publicKey, $this->imageIdentifier);
    }

    /**
     * @covers Imbo\Storage\Filesystem::getLastModified
     */
    public function testGetLastModified() {
        vfsStream::setup('basedir');
        $driver = new Filesystem(array('dataDir' => vfsStream::url('basedir')));

        $root = vfsStreamWrapper::getRoot();
        $last = $root;

        $parts = array(
            $this->publicKey[0],
            $this->publicKey[1],
            $this->publicKey[2],
            $this->publicKey,
            $this->imageIdentifier[0],
            $this->imageIdentifier[1],
            $this->imageIdentifier[2],
        );

        foreach ($parts as $part) {
            $d = vfsStream::newDirectory($part);
            $last->addChild($d);
            $last = $d;
        }

        $now = time();

        $content = 'some binary content';
        $file = vfsStream::newFile($this->imageIdentifier);
        $file->setContent($content);
        $file->lastModified($now);
        $last->addChild($file);

        $this->assertInstanceOf('DateTime', $driver->getLastModified($this->publicKey, $this->imageIdentifier));
        $formatted = $driver->getLastModified($this->publicKey, $this->imageIdentifier, true);
        $this->assertSame($now, strtotime($formatted));
    }
}
