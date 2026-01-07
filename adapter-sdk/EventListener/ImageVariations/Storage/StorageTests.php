<?php declare(strict_types=1);
namespace ImboSDK\EventListener\ImageVariations\Storage;

use Imbo\EventListener\ImageVariations\Storage\StorageInterface;
use Imbo\Exception\StorageException;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;

abstract class StorageTests extends TestCase
{
    protected StorageInterface $adapter;
    protected string $user = 'user';
    protected string $imageIdentifier = 'imageIdentifier';
    protected string $fixturesDir  = __DIR__ . '/../../../Fixtures';
    protected string $imageData;
    protected int $imageWidth;

    /**
     * Get the adapter we want to test
     */
    abstract protected function getAdapter(): StorageInterface;

    protected function setUp(): void
    {
        $this->imageData = (string) file_get_contents($this->fixturesDir . '/image.png');
        $this->imageWidth = 665;
        $this->adapter = $this->getAdapter();
    }

    public function testStoreAndGetImageVariation(): void
    {
        $this->adapter->storeImageVariation($this->user, $this->imageIdentifier, $this->imageData, $this->imageWidth);
        $this->assertSame(
            $this->imageData,
            $this->adapter->getImageVariation($this->user, $this->imageIdentifier, $this->imageWidth),
            'Image variation data is out of sync',
        );
    }

    public function testStoreDeleteAndGetImageVariation(): void
    {
        $this->adapter->storeImageVariation($this->user, $this->imageIdentifier, $this->imageData, $this->imageWidth);
        $this->adapter->deleteImageVariations($this->user, $this->imageIdentifier, $this->imageWidth);
        $this->expectExceptionObject(new StorageException('File not found', 404));
        $this->adapter->getImageVariation($this->user, $this->imageIdentifier, $this->imageWidth);
    }

    #[DoesNotPerformAssertions]
    public function testDeleteImageVariationsThatDoesNotExistDoesNotFail(): void
    {
        $this->adapter->deleteImageVariations($this->user, $this->imageIdentifier);
        $this->adapter->deleteImageVariations($this->user, $this->imageIdentifier, $this->imageWidth);
    }

    public function testGetImageVariationThatDoesNotExist(): void
    {
        $this->expectExceptionObject(new StorageException('File not found', 404));
        $this->adapter->getImageVariation($this->user, $this->imageIdentifier, $this->imageWidth);
    }

    public function testCanDeleteOneOrMoreImageVariations(): void
    {
        $key = 'key';
        $id  = 'imageId';

        $this->adapter->storeImageVariation($key, $id, 'blob1', 100);
        $this->adapter->storeImageVariation($key, $id, 'blob2', 200);
        $this->adapter->storeImageVariation($key, $id, 'blob3', 300);

        $this->assertSame('blob1', $this->adapter->getImageVariation($key, $id, 100));
        $this->assertSame('blob2', $this->adapter->getImageVariation($key, $id, 200));
        $this->assertSame('blob3', $this->adapter->getImageVariation($key, $id, 300));

        $this->adapter->deleteImageVariations($key, $id, 100);

        try {
            $this->adapter->getImageVariation($key, $id, 100);
            $this->fail('Expected StorageException was not thrown');
        } catch (StorageException $e) {
            if (404 !== $e->getCode()) {
                throw $e;
            }
        }
        $this->assertSame('blob2', $this->adapter->getImageVariation($key, $id, 200));
        $this->assertSame('blob3', $this->adapter->getImageVariation($key, $id, 300));

        $this->adapter->deleteImageVariations($key, $id);

        try {
            $r = $this->adapter->getImageVariation($key, $id, 200);
            $this->fail('Expected StorageException was not thrown');
        } catch (StorageException $e) {
            if (404 !== $e->getCode()) {
                throw $e;
            }
        }

        try {
            $r = $this->adapter->getImageVariation($key, $id, 300);
            $this->fail('Expected StorageException was not thrown');
        } catch (StorageException $e) {
            if (404 !== $e->getCode()) {
                throw $e;
            }
        }
    }
}
