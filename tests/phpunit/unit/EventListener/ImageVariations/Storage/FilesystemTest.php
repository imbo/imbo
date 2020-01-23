<?php declare(strict_types=1);
namespace Imbo\EventListener\ImageVariations\Storage;

use Imbo\Exception\StorageException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\EventListener\ImageVariations\Storage\Filesystem
 */
class FilesystemTest extends TestCase {
    public function setUp() : void {
        if (!class_exists(vfsStream::class)) {
            $this->markTestSkipped('This testcase requires vfsStream to run');
        }
    }

    /**
     * @covers ::storeImageVariation
     */
    public function testThrowsExceptionWhenNotAbleToWriteToDirectory() : void {
        $dir = 'unwritableDirectory';
        vfsStream::setup($dir, 0);

        $adapter = new Filesystem(['dataDir' => vfsStream::url($dir)]);
        $this->expectExceptionObject(new StorageException(
            'Could not store image variation (directory not writable)',
            500
        ));
        $adapter->storeImageVariation('pub', 'img', 'blob', 700);
    }

    /**
     * @covers ::deleteImageVariations
     */
    public function testDoesNotThrowWhenDeletingNonExistantVariation() : void {
        $dir = 'basedir';
        vfsStream::setup($dir);

        $adapter = new Filesystem(['dataDir' => vfsStream::url($dir)]);
        $this->assertFalse($adapter->deleteImageVariations('pub', 'img'));
    }
}
