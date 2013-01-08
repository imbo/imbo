<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\Storage;

use Imbo\Storage\Filesystem,
    org\bovigo\vfs\vfsStream,
    org\bovigo\vfs\vfsStreamWrapper;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
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
        if (!class_exists('org\bovigo\vfs\vfsStream')) {
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
     * @covers Imbo\Storage\Filesystem::store
     */
    public function testStore() {
        $imageData = file_get_contents(FIXTURES_DIR . '/image.png');

        $baseDir = 'someDir';

        // Create the virtual directory
        vfsStream::setup($baseDir);

        $driver = new Filesystem(array('dataDir' => vfsStream::url($baseDir)));
        $this->assertTrue($driver->store($this->publicKey, $this->imageIdentifier, $imageData));
    }

    /**
     * @covers Imbo\Storage\Filesystem::getImagePath
     */
    public function testGetImagePath() {
        $driver = new Filesystem(array('dataDir' => '/tmp'));

        $reflection = new \ReflectionClass($driver);
        $method = $reflection->getMethod('getImagePath');
        $method->setAccessible(true);

        $this->assertSame(
            '/tmp/5/9/6/59632bc7a908b9cd47a35d03fc992aa7/9/6/d/96d08a5943ebf1c5635a2995c9408cdd.png',
            $method->invoke($driver, $this->publicKey, $this->imageIdentifier)
        );
        $this->assertSame(
            '/tmp/5/9/6/59632bc7a908b9cd47a35d03fc992aa7/9/6/d',
            $method->invoke($driver, $this->publicKey, $this->imageIdentifier, false)
        );
    }

    /**
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionCode 404
     * @covers Imbo\Storage\Filesystem::getImage
     */
    public function testGetImageFileThatDoesNotExist() {
        $driver = new Filesystem(array('dataDir' => '/tmp'));
        $driver->getImage($this->publicKey, $this->imageIdentifier);
    }

    /**
     * @covers Imbo\Storage\Filesystem::getImage
     */
    public function testGetImage() {
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

        $this->assertSame($content, $driver->getImage($this->publicKey, $this->imageIdentifier));
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
    }

    /**
     * @covers Imbo\Storage\Filesystem::getStatus
     */
    public function testGetStatusWhenBaseDirIsNotWritable() {
        vfsStream::setup('dir', 0);

        $driver = new Filesystem(array('dataDir' => vfsStream::url('dir')));
        $this->assertFalse($driver->getStatus());
    }

    /**
     * @covers Imbo\Storage\Filesystem::getStatus
     */
    public function testGetStatusWhenBaseDirIsWritable() {
        vfsStream::setup('dir');

        $driver = new Filesystem(array('dataDir' => vfsStream::url('dir')));
        $this->assertTrue($driver->getStatus());
    }
}
