<?php declare(strict_types=1);
namespace Imbo\EventListener\ImageVariations\Storage;

use Imbo\Exception\StorageException;
use TestFs\StreamWrapper as TestFs;

/**
 * @coversDefaultClass Imbo\EventListener\ImageVariations\Storage\Filesystem
 */
class FilesystemTest extends StorageTests {
    private $path;

    protected function getAdapter() {
        return new Filesystem([
            'dataDir' => $this->path,
        ]);
    }

    public function setUp() : void {
        TestFs::register();

        $this->path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'imboVariationsFilesystemIntegrationTest';

        if (is_dir($this->path)) {
            $this->rmdir($this->path);
        }

        mkdir($this->path);

        parent::setUp();
    }

    protected function tearDown() : void {
        TestFs::unregister();

        if (is_dir($this->path)) {
            $this->rmdir($this->path);
        }

        parent::tearDown();
    }

    private function rmdir($path) {
        foreach (glob($path . '/*') as $file) {
            if (is_dir($file)) {
                $this->rmdir($file);
            } else {
                unlink($file);
            }
        }

        rmdir($path);
    }

    /**
     * @covers ::storeImageVariation
     */
    public function testThrowsExceptionWhenNotAbleToWriteToDirectory() : void {
        $dir = TestFs::url('dirname');
        mkdir($dir, 0);

        $adapter = new Filesystem(['dataDir' => TestFS::url('someDirectory')]);
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
        $dir = TestFs::url('dirname');
        mkdir($dir);

        $adapter = new Filesystem(['dataDir' => $dir]);
        $this->assertFalse($adapter->deleteImageVariations('pub', 'img'));
    }
}
