<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboIntegrationTest\Storage;

use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * @group integration
 * @group storage
 */
abstract class StorageTests extends TestCase {
    /**
     * @var Imbo\Storage\StorageInterface
     */
    private $driver;

    /**
     * @var string
     */
    private $user = 'key';

    /**
     * @var string
     */
    private $imageIdentifier = '9cb263819af35064af0b6665a1b0fddd';

    /**
     * Binary image data
     *
     * @var string
     */
    private $imageData;

    /**
     * Get the driver we want to test
     *
     * @return Imbo\Storage\StorageInterface
     */
    abstract protected function getDriver();

    /**
     * Get the currently instanced, active driver in inherited tests
     *
     * @return string
     */
    protected function getDriverActive() {
        return $this->driver;
    }

    /**
     * Get the user name in inherited tests
     *
     * @return string
     */
    protected function getUser() {
        return $this->user;
    }

    /**
     * Get the imageIdentifier in inherited tests
     *
     * @return string
     */
    protected function getImageIdentifier() {
        return $this->imageIdentifier;
    }

    /**
     * Set up
     */
    public function setUp() {
        $this->imageData = file_get_contents(FIXTURES_DIR . '/image.png');
        $this->driver = $this->getDriver();
    }

    public function testStoreAndGetImage() {
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

    public function testStoreSameImageTwice() {
        $this->assertTrue(
            $this->driver->store($this->user, $this->imageIdentifier, $this->imageData),
            'Could not store initial image'
        );

        $this->assertInstanceOf(
            DateTime::class,
            $lastModified1 = $this->driver->getLastModified($this->user, $this->imageIdentifier),
            'Last modified of the first image is not a DateTime instance'
        );

        clearstatcache();
        sleep(1);

        $this->assertTrue(
            $this->driver->store($this->user, $this->imageIdentifier, $this->imageData),
            'Could not store image a second time'
        );

        $this->assertInstanceOf(
            DateTime::class,
            $lastModified2 = $this->driver->getLastModified($this->user, $this->imageIdentifier),
            'Last modified of the second image is not a DateTime instance'
        );

        $this->assertTrue(
            $lastModified2 > $lastModified1,
            'Last modification timestamp of second image is not greater than the one of the first image'
        );
    }

    /**
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionCode 404
     */
    public function testStoreDeleteAndGetImage() {
        $this->assertTrue(
            $this->driver->store($this->user, $this->imageIdentifier, $this->imageData),
            'Could not store initial image'
        );

        $this->assertTrue(
            $this->driver->delete($this->user, $this->imageIdentifier),
            'Could not delete image'
        );

        $this->driver->getImage($this->user, $this->imageIdentifier);
    }

    /**
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionCode 404
     */
    public function testDeleteImageThatDoesNotExist() {
        $this->driver->delete($this->user, $this->imageIdentifier);
    }

    /**
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionCode 404
     */
    public function testGetImageThatDoesNotExist() {
        $this->driver->getImage($this->user, $this->imageIdentifier);
    }

    /**
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionCode 404
     */
    public function testGetLastModifiedOfImageThatDoesNotExist() {
        $this->driver->getLastModified($this->user, $this->imageIdentifier);
    }

    public function testGetLastModified() {
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

    public function testCanCheckIfImageAlreadyExists() {
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
