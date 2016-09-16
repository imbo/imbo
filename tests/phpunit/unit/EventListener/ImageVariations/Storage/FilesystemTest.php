<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\EventListener\ImageVariations\Storage;

use Imbo\EventListener\ImageVariations\Storage\Filesystem,
    org\bovigo\vfs\vfsStream,
    org\bovigo\vfs\vfsStreamWrapper;

/**
 * @covers Imbo\EventListener\ImageVariations\Storage\Filesystem
 * @group unit
 * @group storage
 * @group filesystem
 */
class FilesystemTest extends \PHPUnit_Framework_TestCase {
    /**
     * Setup method
     */
    public function setUp() {
        if (!class_exists('org\bovigo\vfs\vfsStream')) {
            $this->markTestSkipped('This testcase requires vfsStream to run');
        }
    }

    /**
     * @covers Imbo\EventListener\ImageVariations\Storage\Filesystem::storeImageVariation
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionMessage Could not store image variation (directory not writable)
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenNotAbleToWriteToDirectory() {
        $dir = 'unwritableDirectory';

        // Create the virtual directory with no permissions
        vfsStream::setup($dir, 0);

        $adapter = new Filesystem(['dataDir' => vfsStream::url($dir)]);
        $adapter->storeImageVariation('pub', 'img', 'blob', 700);
    }

    /**
     * @covers Imbo\EventListener\ImageVariations\Storage\Filesystem::deleteImageVariations
     */
    public function testDoesNotThrowWhenDeletingNonExistantVariation() {
        $dir = 'basedir';
        vfsStream::setup($dir);

        $adapter = new Filesystem(['dataDir' => vfsStream::url($dir)]);
        $this->assertFalse($adapter->deleteImageVariations('pub', 'img'));
    }
}
