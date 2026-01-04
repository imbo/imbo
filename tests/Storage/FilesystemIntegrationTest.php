<?php declare(strict_types=1);
namespace Imbo\Storage;

use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Filesystem::class)]
class FilesystemIntegrationTest extends StorageTests
{
    private string $path;

    protected function getAdapter(): Filesystem
    {
        $this->path = sys_get_temp_dir() . '/imbo-storage-filesystem-integration-test-' . uniqid();
        mkdir($this->path);

        return new Filesystem($this->path);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->path)) {
            $this->rmdir($this->path);
        }

        parent::tearDown();
    }

    private function rmdir(string $path): void
    {
        $paths = glob($path . '/*');

        if (false === $paths) {
            return;
        }

        foreach ($paths as $file) {
            if (is_dir($file)) {
                $this->rmdir($file);
            } else {
                unlink($file);
            }
        }

        rmdir($path);
    }
}
