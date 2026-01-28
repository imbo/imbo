<?php declare(strict_types=1);

namespace Imbo\EventListener\ImageVariations\Storage;

use Imbo\Helpers\Filesystem as FilesystemHelper;
use ImboSDK\EventListener\ImageVariations\Storage\StorageTests;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass(Filesystem::class)]
#[Group('integration')]
class FilesystemIntegrationTest extends StorageTests
{
    private string $baseDir;

    protected function getAdapter(): StorageInterface
    {
        $this->baseDir = sys_get_temp_dir().'/imbo-eventlistener-imagevariations-storage-filesystem-integration-test-'.uniqid();
        FilesystemHelper::removeDir($this->baseDir, true);

        return new Filesystem($this->baseDir);
    }

    protected function tearDown(): void
    {
        FilesystemHelper::removeDir($this->baseDir);
        parent::tearDown();
    }
}
