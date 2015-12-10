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

/**
 * @group integration
 * @group storage
 */
abstract class StorageTests extends \PHPUnit_Framework_TestCase {
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
     * Set up
     */
    public function setUp() {
        $this->imageData = file_get_contents(FIXTURES_DIR . '/image.png');
        $this->driver = $this->getDriver();
    }

    /**
     * Tear down
     */
    public function tearDown() {
        $this->driver = null;
    }

    public function testStoreAndGetImage() {
        $this->assertTrue($this->driver->store($this->user, $this->imageIdentifier, $this->imageData));
        $this->assertSame($this->imageData, $this->driver->getImage($this->user, $this->imageIdentifier));
    }

    public function testStoreSameImageTwice() {
        $this->assertTrue($this->driver->store($this->user, $this->imageIdentifier, $this->imageData));
        $lastModified1 = $this->driver->getLastModified($this->user, $this->imageIdentifier);
        clearstatcache();
        sleep(1);
        $this->assertTrue($this->driver->store($this->user, $this->imageIdentifier, $this->imageData));
        $lastModified2 = $this->driver->getLastModified($this->user, $this->imageIdentifier);

        $this->assertTrue($lastModified2 > $lastModified1);
    }

    /**
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionCode 404
     */
    public function testStoreDeleteAndGetImage() {
        $this->assertTrue($this->driver->store($this->user, $this->imageIdentifier, $this->imageData));
        $this->assertTrue($this->driver->delete($this->user, $this->imageIdentifier));
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
        $this->assertTrue($this->driver->store($this->user, $this->imageIdentifier, $this->imageData));
        $this->assertInstanceOf('DateTime', $this->driver->getLastModified($this->user, $this->imageIdentifier));
    }

    public function testCanCheckIfImageAlreadyExists() {
        $this->assertFalse($this->driver->imageExists($this->user, $this->imageIdentifier));
        $this->driver->store($this->user, $this->imageIdentifier, $this->imageData);
        $this->assertTrue($this->driver->imageExists($this->user, $this->imageIdentifier));
    }
}
