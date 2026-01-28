<?php declare(strict_types=1);

namespace Imbo\Storage;

use Imbo\Helpers\Filesystem as FilesystemHelper;
use ImboSDK\Storage\StorageTests;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass(Filesystem::class)]
#[Group('integration')]
class FilesystemIntegrationTest extends StorageTests
{
    private string $baseDir;

    protected function getAdapter(): Filesystem
    {
        $this->baseDir = sys_get_temp_dir().'/imbo-storage-filesystem-integration-test-'.uniqid();
        FilesystemHelper::removeDir($this->baseDir, true);

        return new Filesystem($this->baseDir);
    }

    protected function tearDown(): void
    {
        FilesystemHelper::removeDir($this->baseDir);
        parent::tearDown();
    }
}
