<?php declare(strict_types=1);
namespace Imbo\Storage;

use Imbo\Storage\Filesystem;
use Imbo\Exception\ConfigurationException;
use Imbo\Exception\StorageException;
use PHPUnit\Framework\TestCase;
use TestFs\StreamWrapper as TestFs;

/**
 * @coversDefaultClass Imbo\Storage\Filesystem
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

    public function setUp() : void {
        TestFs::register();
    }

    public function tearDown() : void {
        TestFs::unregister();
    }

    /**
     * @covers ::delete
     */
    public function testDeleteFileThatDoesNotExist() : void {
        $driver = new Filesystem(['dataDir' => TestFs::url('foobar')]);
        $this->expectExceptionObject(new StorageException('File not found', 404));
        $driver->delete($this->user, $this->imageIdentifier);
    }

    /**
     * @covers ::delete
     */
    public function testDelete() : void {
        $driver = new Filesystem(['dataDir' => TestFs::url('basedir')]);

        $dir = TestFs::url(join('/', [
            'basedir',
            $this->user[0],
            $this->user[1],
            $this->user[2],
            $this->user,
            $this->imageIdentifier[0],
            $this->imageIdentifier[1],
            $this->imageIdentifier[2],
        ]));
        $filePath = sprintf('%s/%s', $dir , $this->imageIdentifier);

        mkdir($dir, 0777, true);
        touch($filePath);

        $this->assertTrue(is_file($filePath), 'Expected file to exist');
        $driver->delete($this->user, $this->imageIdentifier);
        clearstatcache();
        $this->assertFalse(is_file($filePath), 'Did not expect file to exist');
    }

    /**
     * @covers ::store
     */
    public function testStoreToUnwritablePath() : void {
        $image = 'some image data';
        $dir = TestFs::url('unwritableDirectory');

        mkdir($dir, 0000);

        $driver = new Filesystem(['dataDir' => $dir]);
        $this->expectExceptionObject(new StorageException('Could not store image', 500));
        $driver->store($this->user, $this->imageIdentifier, $image);
    }

    /**
     * @covers ::store
     * @covers ::getImagePath
     */
    public function testStore() : void {
        $imageData = file_get_contents(FIXTURES_DIR . '/image.png');

        $baseDir = TestFs::url('someDir');
        mkdir($baseDir);

        $driver = new Filesystem(['dataDir' => $baseDir]);
        $this->assertTrue($driver->store($this->user, $this->imageIdentifier, $imageData));

        $this->assertTrue(is_file(TestFs::url('someDir/5/9/6/59632bc7a908b9cd47a35d03fc992aa7/9/6/d/96d08a5943ebf1c5635a2995c9408cdd.png')), 'Expected file to exist');
    }

    /**
     * @covers ::getImage
     */
    public function testGetImageFileThatDoesNotExist() : void {
        $driver = new Filesystem(['dataDir' => '/tmp']);
        $this->expectExceptionObject(new StorageException('File not found', 404));
        $driver->getImage($this->user, $this->imageIdentifier);
    }

    /**
     * @covers ::getImage
     */
    public function testGetImage() : void {
        $dir = TestFs::url('basedir');
        mkdir($dir);
        $driver = new Filesystem(['dataDir' => $dir]);

        $filePath = TestFs::url(join('/', [
            'basedir',
            $this->user[0],
            $this->user[1],
            $this->user[2],
            $this->user,
            $this->imageIdentifier[0],
            $this->imageIdentifier[1],
            $this->imageIdentifier[2],
            $this->imageIdentifier,
        ]));

        mkdir(dirname($filePath), 0777, true);
        file_put_contents($filePath, 'some content');

        $this->assertSame('some content', $driver->getImage($this->user, $this->imageIdentifier));
    }

    /**
     * @covers ::getLastModified
     */
    public function testGetLastModifiedWithFileThatDoesNotExist() : void {
        $driver = new Filesystem(['dataDir' => '/some/path']);
        $this->expectExceptionObject(new StorageException('File not found', 404));
        $driver->getLastModified($this->user, $this->imageIdentifier);
    }

    /**
     * @covers ::getLastModified
     */
    public function testGetLastModified() : void {
        $dir = TestFs::url('basedir');
        $driver = new Filesystem(['dataDir' => $dir]);

        $filePath = TestFs::url(join('/', [
            'basedir',
            $this->user[0],
            $this->user[1],
            $this->user[2],
            $this->user,
            $this->imageIdentifier[0],
            $this->imageIdentifier[1],
            $this->imageIdentifier[2],
            $this->imageIdentifier
        ]));

        mkdir(dirname($filePath), 0777, true);
        file_put_contents($filePath, 'some content');

        $this->assertInstanceOf('DateTime', $driver->getLastModified($this->user, $this->imageIdentifier));
    }

    /**
     * @covers ::getStatus
     */
    public function testGetStatusWhenBaseDirIsNotWritable() : void {
        $dir = TestFs::url('dir');
        mkdir($dir, 0000);
        $driver = new Filesystem(['dataDir' => $dir]);
        $this->assertFalse($driver->getStatus());
    }

    /**
     * @covers ::getStatus
     */
    public function testGetStatusWhenBaseDirIsWritable() : void {
        $dir = TestFs::url('dir');
        mkdir($dir);
        $driver = new Filesystem(['dataDir' => $dir]);
        $this->assertTrue($driver->getStatus());
    }

    /**
     * @covers ::__construct
     */
    public function testMissingDataDir() : void {
        $this->expectExceptionObject(new ConfigurationException(
            'Missing required parameter dataDir in the Filesystem storage driver.',
            500
        ));
        new Filesystem([]);
    }
}
