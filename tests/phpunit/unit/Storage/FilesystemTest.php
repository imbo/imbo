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

use Imbo\Storage\Filesystem;
use Imbo\Exception\ConfigurationException;
use Imbo\Exception\StorageException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use PHPUnit\Framework\TestCase;

/**
 * @covers Imbo\Storage\Filesystem
 * @group unit
 * @group storage
 */
class FilesystemTest extends TestCase {
    /**
     * User that can be used in tests
     *
     * @var string
     */
    private $user = '59632bc7a908b9cd47a35d03fc992aa7';

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
     * @covers Imbo\Storage\Filesystem::delete
     */
    public function testDeleteFileThatDoesNotExist() {
        $driver = new Filesystem(['dataDir' => 'foobar']);
        $this->expectExceptionObject(new StorageException('File not found', 404));
        $driver->delete($this->user, $this->imageIdentifier);
    }

    /**
     * @covers Imbo\Storage\Filesystem::delete
     */
    public function testDelete() {
        vfsStream::setup('basedir');
        $driver = new Filesystem(['dataDir' => vfsStream::url('basedir')]);

        $root = vfsStreamWrapper::getRoot();
        $last = $root;

        $parts = [
            $this->user[0],
            $this->user[1],
            $this->user[2],
            $this->user,
            $this->imageIdentifier[0],
            $this->imageIdentifier[1],
            $this->imageIdentifier[2],
        ];

        foreach ($parts as $part) {
            $d = vfsStream::newDirectory($part);
            $last->addChild($d);
            $last = $d;
        }

        $last->addChild(vfsStream::newFile($this->imageIdentifier));

        $this->assertTrue($last->hasChild($this->imageIdentifier));
        $driver->delete($this->user, $this->imageIdentifier);
        $this->assertFalse($last->hasChild($this->imageIdentifier));
    }

    /**
     * @covers Imbo\Storage\Filesystem::store
     */
    public function testStoreToUnwritablePath() {
        $image = 'some image data';
        $dir = 'unwritableDirectory';

        // Create the virtual directory with no permissions
        vfsStream::setup($dir, 0);

        $driver = new Filesystem(['dataDir' => vfsStream::url($dir)]);
        $this->expectExceptionObject(new StorageException('Could not store image', 500));
        $driver->store($this->user, $this->imageIdentifier, $image);
    }

    /**
     * @covers Imbo\Storage\Filesystem::store
     */
    public function testStore() {
        $imageData = file_get_contents(FIXTURES_DIR . '/image.png');

        $baseDir = 'someDir';

        // Create the virtual directory
        vfsStream::setup($baseDir);

        $driver = new Filesystem(['dataDir' => vfsStream::url($baseDir)]);
        $this->assertTrue($driver->store($this->user, $this->imageIdentifier, $imageData));
    }

    /**
     * @covers Imbo\Storage\Filesystem::getImagePath
     */
    public function testGetImagePath() {
        $driver = new Filesystem(['dataDir' => DIRECTORY_SEPARATOR . 'tmp']);

        $reflection = new \ReflectionClass($driver);
        $method = $reflection->getMethod('getImagePath');
        $method->setAccessible(true);

        $expectedFullPath = '/tmp/5/9/6/59632bc7a908b9cd47a35d03fc992aa7/9/6/d/96d08a5943ebf1c5635a2995c9408cdd.png';
        $expectedDirPath = dirname($expectedFullPath);

        if (DIRECTORY_SEPARATOR != '/') {
            $expectedFullPath = str_replace('/', DIRECTORY_SEPARATOR, $expectedFullPath);
            $expectedDirPath = str_replace('/', DIRECTORY_SEPARATOR, $expectedDirPath);
        }

        $this->assertSame(
            $expectedFullPath,
            $method->invoke($driver, $this->user, $this->imageIdentifier)
        );
        $this->assertSame(
            $expectedDirPath,
            $method->invoke($driver, $this->user, $this->imageIdentifier, false)
        );
    }

    /**
     * @covers Imbo\Storage\Filesystem::getImage
     */
    public function testGetImageFileThatDoesNotExist() {
        $driver = new Filesystem(['dataDir' => '/tmp']);
        $this->expectExceptionObject(new StorageException('File not found', 404));
        $driver->getImage($this->user, $this->imageIdentifier);
    }

    /**
     * @covers Imbo\Storage\Filesystem::getImage
     */
    public function testGetImage() {
        vfsStream::setup('basedir');
        $driver = new Filesystem(['dataDir' => vfsStream::url('basedir')]);

        $root = vfsStreamWrapper::getRoot();
        $last = $root;

        $parts = [
            $this->user[0],
            $this->user[1],
            $this->user[2],
            $this->user,
            $this->imageIdentifier[0],
            $this->imageIdentifier[1],
            $this->imageIdentifier[2],
        ];

        foreach ($parts as $part) {
            $d = vfsStream::newDirectory($part);
            $last->addChild($d);
            $last = $d;
        }

        $content = 'some binary content';
        $file = vfsStream::newFile($this->imageIdentifier);
        $file->setContent($content);
        $last->addChild($file);

        $this->assertSame($content, $driver->getImage($this->user, $this->imageIdentifier));
    }

    /**
     * @covers Imbo\Storage\Filesystem::getLastModified
     */
    public function testGetLastModifiedWithFileThatDoesNotExist() {
        $driver = new Filesystem(['dataDir' => '/some/path']);
        $this->expectExceptionObject(new StorageException('File not found', 404));
        $driver->getLastModified($this->user, $this->imageIdentifier);
    }

    /**
     * @covers Imbo\Storage\Filesystem::getLastModified
     */
    public function testGetLastModified() {
        vfsStream::setup('basedir');
        $driver = new Filesystem(['dataDir' => vfsStream::url('basedir')]);

        $root = vfsStreamWrapper::getRoot();
        $last = $root;

        $parts = [
            $this->user[0],
            $this->user[1],
            $this->user[2],
            $this->user,
            $this->imageIdentifier[0],
            $this->imageIdentifier[1],
            $this->imageIdentifier[2],
        ];

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

        $this->assertInstanceOf('DateTime', $driver->getLastModified($this->user, $this->imageIdentifier));
    }

    /**
     * @covers Imbo\Storage\Filesystem::getStatus
     */
    public function testGetStatusWhenBaseDirIsNotWritable() {
        vfsStream::setup('dir', 0);

        $driver = new Filesystem(['dataDir' => vfsStream::url('dir')]);
        $this->assertFalse($driver->getStatus());
    }

    /**
     * @covers Imbo\Storage\Filesystem::getStatus
     */
    public function testGetStatusWhenBaseDirIsWritable() {
        vfsStream::setup('dir');

        $driver = new Filesystem(['dataDir' => vfsStream::url('dir')]);
        $this->assertTrue($driver->getStatus());
    }

    public function testMissingDataDir() {
        $this->expectExceptionObject(new ConfigurationException(
            'Missing required parameter dataDir in the Filesystem storage driver.',
            500
        ));
        new Filesystem([]);
    }
}
