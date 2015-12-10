<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboIntegrationTest\Storage;

use Imbo\Storage\Filesystem;

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

    public function setUp() {
        $this->path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'imboFilesystemIntegrationTest';

        if (is_dir($this->path)) {
            $this->rmdir($this->path);
        }

        mkdir($this->path);

        parent::setUp();
    }

    public function tearDown() {
        if (is_dir($this->path)) {
            $this->rmdir($this->path);
        }

        parent::tearDown();
    }

    /**
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionCode 507
     */
    public function testStoringEmptyDataFails() {
        $this->getDriverActive()->store($this->getUser(), "this_identifier_left_empty", "");
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
