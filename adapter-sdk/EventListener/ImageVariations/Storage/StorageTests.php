<?php declare(strict_types=1);
namespace ImboSDK\EventListener\ImageVariations\Storage;

use Imbo\EventListener\ImageVariations\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;

abstract class StorageTests extends TestCase
{
    protected StorageInterface $adapter;
    protected string $fixturesDir  = __DIR__ . '/../../../Fixtures';

    /**
     * Get the adapter we want to test
     */
    abstract protected function getAdapter(): StorageInterface;

    protected function setUp(): void
    {
        $this->adapter = $this->getAdapter();
    }

    public function testCanStoreAndGetImageVariations(): void
    {
        $key = 'key';
        $id  = 'imageId';
        $width = 200;
        $blob = file_get_contents($this->fixturesDir . '/image.jpg');

        $this->assertNull(
            $this->adapter->getImageVariation($key, $id, $width),
            'Image variation should not exist',
        );

        $this->assertTrue(
            $this->adapter->storeImageVariation($key, $id, (string) $blob, $width),
            'Could not store image variation',
        );

        $this->assertSame(
            $blob,
            $this->adapter->getImageVariation($key, $id, $width),
            'Image variation data out of sync',
        );
    }

    public function testCanDeleteOneOrMoreImageVariations(): void
    {
        $key = 'key';
        $id  = 'imageId';
        $blob = file_get_contents($this->fixturesDir . '/image.jpg');

        $this->assertTrue(
            $this->adapter->storeImageVariation($key, $id, (string) $blob, 100),
            'Could not store 1st image variation',
        );

        $this->assertTrue(
            $this->adapter->storeImageVariation($key, $id, 'blob2', 200),
            'Could not store 2nd image variation',
        );

        $this->assertTrue(
            $this->adapter->storeImageVariation($key, $id, 'blob3', 300),
            'Could not store 3rd image variation',
        );

        $this->assertSame($blob, $this->adapter->getImageVariation($key, $id, 100));
        $this->assertSame('blob2', $this->adapter->getImageVariation($key, $id, 200));
        $this->assertSame('blob3', $this->adapter->getImageVariation($key, $id, 300));

        $this->assertTrue($this->adapter->deleteImageVariations($key, $id, 100));
        $this->assertNull($this->adapter->getImageVariation($key, $id, 100));
        $this->assertSame('blob2', $this->adapter->getImageVariation($key, $id, 200));
        $this->assertSame('blob3', $this->adapter->getImageVariation($key, $id, 300));

        $this->assertTrue($this->adapter->deleteImageVariations($key, $id));
        $this->assertNull($this->adapter->getImageVariation($key, $id, 200));
        $this->assertNull($this->adapter->getImageVariation($key, $id, 300));
    }
}
