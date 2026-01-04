<?php declare(strict_types=1);
namespace Imbo\EventListener\ImageVariations\Storage;

use Imbo\Exception\StorageException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TestFs\StreamWrapper as TestFs;

#[CoversClass(Filesystem::class)]
class FilesystemTest extends TestCase
{
    public function setUp(): void
    {
        TestFs::register();
    }

    protected function tearDown(): void
    {
        TestFs::unregister();
    }

    public function testThrowsExceptionWhenNotAbleToWriteToDirectory(): void
    {
        $dir = TestFs::url('dirname');
        mkdir($dir, 0);

        $adapter = new Filesystem(TestFS::url('someDirectory'));
        $this->expectExceptionObject(new StorageException(
            'Could not store image variation (directory not writable)',
            500,
        ));
        $adapter->storeImageVariation('pub', 'img', 'blob', 700);
    }

    public function testDoesNotThrowWhenDeletingNonExistantVariation(): void
    {
        $dir = TestFs::url('dirname');
        mkdir($dir);

        $adapter = new Filesystem($dir);
        $this->assertFalse($adapter->deleteImageVariations('pub', 'img'));
    }

    public function testCanGetImageVariation(): void
    {
        $blob = file_get_contents(__DIR__ . '/../../../Fixtures/image.png');
        $imagePath = TestFs::url('someDir/u/s/e/user/i/m/a/image/300');
        mkdir(dirname($imagePath), 0000, true);
        file_put_contents($imagePath, $blob);

        $adapter = new Filesystem(TestFs::url('someDir'));

        $this->assertSame($blob, $adapter->getImageVariation('user', 'image', 300));
    }
}
