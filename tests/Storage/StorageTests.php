<?php declare(strict_types=1);
namespace Imbo\Storage;

use Imbo\Exception\StorageException;
use DateTime;
use PHPUnit\Framework\TestCase;

abstract class StorageTests extends TestCase {
    protected $driver;
    protected $user = 'key';
    protected $imageIdentifier = '9cb263819af35064af0b6665a1b0fddd';
    protected $imageData;

    /**
     * Get the driver we want to test
     *
     * @return StorageInterface
     */
    abstract protected function getDriver();

    public function setUp() : void {
        $this->imageData = file_get_contents(FIXTURES_DIR . '/image.png');
        $this->driver = $this->getDriver();
    }

    /**
     * @covers ::store
     */
    public function testStoreAndGetImage() : void {
        $this->assertTrue(
            $this->driver->store($this->user, $this->imageIdentifier, $this->imageData),
            'Could not store initial image'
        );

        $this->assertSame(
            $this->imageData,
            $this->driver->getImage($this->user, $this->imageIdentifier),
            'Image data is out of sync'
        );
    }

    /**
     * @covers ::store
     * @covers ::delete
     * @covers ::getImage
     */
    public function testStoreDeleteAndGetImage() : void {
        $this->assertTrue(
            $this->driver->store($this->user, $this->imageIdentifier, $this->imageData),
            'Could not store initial image'
        );

        $this->assertTrue(
            $this->driver->delete($this->user, $this->imageIdentifier),
            'Could not delete image'
        );

        $this->expectExceptionObject(new StorageException('File not found', 404));

        $this->driver->getImage($this->user, $this->imageIdentifier);
    }

    /**
     * @covers ::delete
     */
    public function testDeleteImageThatDoesNotExist() : void {
        $this->expectExceptionObject(new StorageException('File not found', 404));
        $this->driver->delete($this->user, $this->imageIdentifier);
    }

    /**
     * @covers ::getImage
     */
    public function testGetImageThatDoesNotExist() : void {
        $this->expectExceptionObject(new StorageException('File not found', 404));
        $this->driver->getImage($this->user, $this->imageIdentifier);
    }

    /**
     * @covers ::getLastModified
     */
    public function testGetLastModifiedOfImageThatDoesNotExist() : void {
        $this->expectExceptionObject(new StorageException('File not found', 404));
        $this->driver->getLastModified($this->user, $this->imageIdentifier);
    }

    /**
     * @covers ::store
     * @covers ::getLastModified
     */
    public function testGetLastModified() : void {
        $this->assertTrue(
            $this->driver->store($this->user, $this->imageIdentifier, $this->imageData),
            'Could not store initial image'
        );
        $this->assertInstanceOf(
            DateTime::class,
            $this->driver->getLastModified($this->user, $this->imageIdentifier),
            'Last modification is not an instance of DateTime'
        );
    }

    /**
     * @covers ::imageExists
     * @covers ::store
     */
    public function testCanCheckIfImageAlreadyExists() : void {
        $this->assertFalse(
            $this->driver->imageExists($this->user, $this->imageIdentifier),
            'Image is not supposed to exist'
        );

        $this->assertTrue(
            $this->driver->store($this->user, $this->imageIdentifier, $this->imageData),
            'Could not store image'
        );

        $this->assertTrue(
            $this->driver->imageExists($this->user, $this->imageIdentifier),
            'Image does not exist'
        );
    }
}
