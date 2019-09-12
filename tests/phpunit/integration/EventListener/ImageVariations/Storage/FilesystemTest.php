<?php
namespace ImboIntegrationTest\EventListener\ImageVariations\Storage;

use Imbo\EventListener\ImageVariations\Storage\Filesystem;

/**
 * @covers Imbo\EventListener\ImageVariations\Storage\Filesystem
 * @group integration
 * @group storage
 * @group filesystem
 */
class FilesystemTest extends StorageTests {
    /**
     * @var string
     */
    private $path = null;

    /**
     * @see ImboIntegrationTest\Storage\StorageTests::getAdapter()
     */
    protected function getAdapter() {
        return new Filesystem([
            'dataDir' => $this->path,
        ]);
    }

    /**
     * Set up the directory for each test, ensuring it's empty
     */
    public function setUp() : void {
        $this->path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'imboVariationsFilesystemIntegrationTest';

        if (is_dir($this->path)) {
            $this->rmdir($this->path);
        }

        mkdir($this->path);

        parent::setUp();
    }

    /**
     * Clean up directory structure after each test
     */
    protected function tearDown() : void {
        if (is_dir($this->path)) {
            $this->rmdir($this->path);
        }

        parent::tearDown();
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
