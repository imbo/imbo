<?php declare(strict_types=1);

namespace Imbo\Behat\StorageTest;

use Imbo\Behat\IntegrationTestAdapter;
use Imbo\Storage\Filesystem as StorageAdapter;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use const DIRECTORY_SEPARATOR;

class Filesystem implements IntegrationTestAdapter
{
    private string $baseDir;

    public function __construct()
    {
        $this->baseDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'imbo_behat_test_storage';
    }

    public function setUp(): void
    {
        if (!is_dir($this->baseDir)) {
            mkdir($this->baseDir);

            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->baseDir),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            $name = $file->getPathname();

            if ('.' === substr($name, -1)) {
                continue;
            }

            if ($file->isDir()) {
                rmdir($name);
            } else {
                unlink($name);
            }
        }
    }

    public function getAdapter(): StorageAdapter
    {
        return new StorageAdapter($this->baseDir);
    }
}
