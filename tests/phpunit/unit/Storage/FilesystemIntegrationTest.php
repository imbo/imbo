<?php declare(strict_types=1);
namespace Imbo\Storage;

use Imbo\Exception\StorageException;

/**
 * @coversDefaultClass Imbo\Storage\Filesystem
 */
class FilesystemIntegrationTest extends StorageTests {
    private $path;

    protected function getDriver() {
        return new Filesystem([
            'dataDir' => $this->path,
        ]);
    }

    public function setUp() : void {
        $this->path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'imboFilesystemIntegrationTest';

        if (is_dir($this->path)) {
            $this->rmdir($this->path);
        }

        mkdir($this->path);

        parent::setUp();
    }

    protected function tearDown() : void {
        if (is_dir($this->path)) {
            $this->rmdir($this->path);
        }

        parent::tearDown();
    }

    public function testStoringEmptyDataFails() : void {
        $this->expectExceptionObject(new StorageException(
            'Failed writing file (disk full? zero bytes input?) to disk:',
            507
        ));
        $this->driver->store($this->user, 'this_identifier_left_empty', '');
    }

    /**
     * Recursively delete the test directory
     *
     * @param string $path Path to a file or a directory
     */
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
}
