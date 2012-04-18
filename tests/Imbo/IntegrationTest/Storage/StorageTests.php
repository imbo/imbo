<?php
/**
 * Imbo
 *
 * Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * * The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package TestSuite\IntegrationTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\IntegrationTest\Storage;

/**
 * @package TestSuite\IntegrationTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
abstract class StorageTests extends \PHPUnit_Framework_TestCase {
    /**
     * @var Imbo\Storage\StorageInterface
     */
    private $driver;

    /**
     * @var string
     */
    private $publicKey = 'key';

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
        $this->assertTrue($this->driver->store($this->publicKey, $this->imageIdentifier, $this->imageData));
        $this->assertSame($this->imageData, $this->driver->getImage($this->publicKey, $this->imageIdentifier));
    }

    /**
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Image already exists
     */
    public function testStoreSameImageTwice() {
        $this->assertTrue($this->driver->store($this->publicKey, $this->imageIdentifier, $this->imageData));
        $this->driver->store($this->publicKey, $this->imageIdentifier, $this->imageData);
    }

    /**
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionCode 404
     */
    public function testStoreDeleteAndGetImage() {
        $this->assertTrue($this->driver->store($this->publicKey, $this->imageIdentifier, $this->imageData));
        $this->assertTrue($this->driver->delete($this->publicKey, $this->imageIdentifier));
        $this->driver->getImage($this->publicKey, $this->imageIdentifier);
    }

    /**
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionCode 404
     */
    public function testDeleteImageThatDoesNotExist() {
        $this->driver->delete($this->publicKey, $this->imageIdentifier);
    }

    /**
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionCode 404
     */
    public function testGetImageThatDoesNotExist() {
        $this->driver->getImage($this->publicKey, $this->imageIdentifier);
    }

    /**
     * @expectedException Imbo\Exception\StorageException
     * @expectedExceptionCode 404
     */
    public function testGetLastModifiedOfImageThatDoesNotExist() {
        $this->driver->getLastModified($this->publicKey, $this->imageIdentifier);
    }

    public function testGetLastModified() {
        $this->assertTrue($this->driver->store($this->publicKey, $this->imageIdentifier, $this->imageData));
        $this->assertInstanceOf('DateTime', $this->driver->getLastModified($this->publicKey, $this->imageIdentifier));
    }
}
