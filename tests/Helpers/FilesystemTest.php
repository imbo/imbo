<?php declare(strict_types=1);

namespace Imbo\Helpers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TestFs\StreamWrapper as TestFs;

#[CoversClass(Filesystem::class)]
class FilesystemTest extends TestCase
{
    protected function setUp(): void
    {
        if (!TestFs::register()) {
            $this->fail('Unable to register stream wrapper');
        }
    }

    protected function tearDown(): void
    {
        TestFs::unregister();
    }

    public function testRemoveNonExistingDirectoryWithoutRecreate(): void
    {
        $baseDir = TestFs::url('nonexistingdir');
        Filesystem::removeDir($baseDir);
        clearstatcache();
        $this->assertFalse(is_dir($baseDir), 'Directory should not exist');
    }

    public function testRemoveNonExistingDirectoryWithRecreate(): void
    {
        $baseDir = TestFs::url('nonexistingdir');
        Filesystem::removeDir($baseDir, true);
        clearstatcache();
        $this->assertTrue(is_dir($baseDir), 'Directory should exist');
    }

    public function testRemoveEmptyDirectoryWithoutRecreate(): void
    {
        $baseDir = TestFs::url('base');
        mkdir($baseDir);

        Filesystem::removeDir($baseDir);
        clearstatcache();
        $this->assertFalse(is_dir($baseDir), 'Directory should not exist');
    }

    public function testRemoveEmptyDirectoryWithRecreate(): void
    {
        $baseDir = TestFs::url('base');
        mkdir($baseDir);

        Filesystem::removeDir($baseDir, true);
        clearstatcache();
        $this->assertTrue(is_dir($baseDir), 'Directory should exist');
    }

    public function testRemoveNonEmptyDirectoryWithoutRecreate(): void
    {
        $baseDir = TestFs::url('base');
        mkdir($baseDir);
        touch($baseDir.'/file.txt');
        mkdir($baseDir.'/sub');
        touch($baseDir.'/sub/file.txt');

        Filesystem::removeDir($baseDir);
        clearstatcache();
        $this->assertFalse(is_dir($baseDir), 'Directory should not exist');
    }

    public function testRemoveNonEmptyDirectoryWithRecreate(): void
    {
        $baseDir = TestFs::url('base');
        mkdir($baseDir);
        touch($baseDir.'/file.txt');
        mkdir($baseDir.'/sub');
        touch($baseDir.'/sub/file.txt');

        Filesystem::removeDir($baseDir, true);
        clearstatcache();
        $this->assertTrue(is_dir($baseDir), 'Directory should exist');
    }
}
