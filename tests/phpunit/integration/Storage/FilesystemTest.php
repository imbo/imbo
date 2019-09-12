<?php
namespace ImboIntegrationTest\Storage;

use Imbo\Storage\Filesystem;
use Imbo\Exception\StorageException;

/**
 * @covers Imbo\Storage\Filesystem
 * @group integration
 * @group storage
 */
class FilesystemTest extends StorageTests {
    /**
     * @var string
     */
    private $path = null;

    /**
     * @see ImboIntegrationTest\Storage\StorageTests::getDriver()
     */
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

    public function testStoringEmptyDataFails() {
        $this->expectExceptionObject(new StorageException(
            'Failed writing file (disk full? zero bytes input?) to disk:',
            507
        ));
        $this->getDriverActive()->store($this->getUser(), 'this_identifier_left_empty', '');
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
