<?php declare(strict_types=1);

namespace Imbo\Behat\StorageTest;

use Imbo\Behat\IntegrationTestAdapter;
use Imbo\Helpers\Filesystem as FilesystemHelper;
use Imbo\Storage\Filesystem as StorageAdapter;

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
        FilesystemHelper::removeDir($this->baseDir, true);
    }

    public function getAdapter(): StorageAdapter
    {
        return new StorageAdapter($this->baseDir);
    }
}
