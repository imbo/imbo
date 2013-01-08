<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\Image;

use Imbo\Image\Image;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 * @covers Imbo\Image\Image
 */
class ImageTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Imbo\Image\Image
     */
    private $image;

    /**
     * Set up the image instance
     */
    public function setUp() {
        $this->image = new Image();
    }

    /**
     * Tear down the image instance
     */
    public function tearDown() {
        $this->image = null;
    }

    /**
     * @covers Imbo\Image\Image::setMetadata
     * @covers Imbo\Image\Image::getMetadata
     */
    public function testCanSetAndGetMetadata() {
        $data = array(
            'foo' => 'bar',
            'bar' => 'foo',
        );
        $this->assertSame($this->image, $this->image->setMetadata($data));
        $this->assertSame($data, $this->image->getMetadata());
    }

    /**
     * @covers Imbo\Image\Image::setMimeType
     * @covers Imbo\Image\Image::getMimeType
     */
    public function testCanSetAndGetMimeType() {
        $mimeType = 'image/png';
        $this->assertSame($this->image, $this->image->setMimeType($mimeType));
        $this->assertSame($mimeType, $this->image->getMimeType());
    }

    /**
     * @covers Imbo\Image\Image::setBlob
     * @covers Imbo\Image\Image::getBlob
     * @covers Imbo\Image\Image::getFilesize
     * @covers Imbo\Image\Image::getChecksum
     */
    public function testCanSetAndGetBlob() {
        $blob = 'some string';
        $hash = md5($blob);
        $this->assertSame($this->image, $this->image->setBlob($blob));
        $this->assertSame($blob, $this->image->getBlob());
        $this->assertSame(11, $this->image->getFilesize());
        $this->assertSame($hash, $this->image->getChecksum());

        $blob = 'some other string';
        $hash = md5($blob);
        $this->assertSame($this->image, $this->image->setBlob($blob));
        $this->assertSame($blob, $this->image->getBlob());
        $this->assertSame(17, $this->image->getFilesize());
        $this->assertSame($hash, $this->image->getChecksum());
    }

    /**
     * @covers Imbo\Image\Image::setExtension
     * @covers Imbo\Image\Image::getExtension
     */
    public function testCanSetAndGetExtension() {
        $extension = 'png';
        $this->assertSame($this->image, $this->image->setExtension($extension));
        $this->assertSame($extension, $this->image->getExtension());
    }

    /**
     * @covers Imbo\Image\Image::setWidth
     * @covers Imbo\Image\Image::getWidth
     */
    public function testCanSetAndGetWidth() {
        $width = 123;
        $this->assertSame($this->image, $this->image->setWidth($width));
        $this->assertSame($width, $this->image->getWidth());
    }

    /**
     * @covers Imbo\Image\Image::setHeight
     * @covers Imbo\Image\Image::getHeight
     */
    public function testCanSetAndGetHeight() {
        $height = 234;
        $this->assertSame($this->image, $this->image->setHeight($height));
        $this->assertSame($height, $this->image->getHeight());
    }

    /**
     * Get mime types and whether or not they are supported
     *
     * @return array
     */
    public function getMimeTypes() {
        return array(
            array('image/png', true),
            array('image/jpeg', true),
            array('image/gif', true),
            array('image/jpg', false),
        );
    }

    /**
     * @covers Imbo\Image\Image::supportedMimeType
     * @dataProvider getMimeTypes
     */
    public function testCanInformAboutSupportedMimeType($type, $result) {
        $this->assertSame($result, Image::supportedMimeType($type));
    }

    /**
     * Get mime types and file extensions
     *
     * @return array
     */
    public function getFileExtensions() {
        return array(
            array('image/png', 'png'),
            array('image/jpeg', 'jpg'),
            array('image/gif', 'gif'),
            array('image/jpg', false),
        );
    }

    /**
     * @covers Imbo\Image\Image::getFileExtension
     * @dataProvider getFileExtensions
     */
    public function testCanGetAFileExtensionBasedOnAMimeType($type, $extension) {
        $this->assertSame($extension, Image::getFileExtension($type));
    }

    /**
     * @covers Imbo\Image\Image::hasBeenTransformed
     */
    public function testCanMarkIfTheImageHasBeenTransformedOrNot() {
        $this->assertFalse($this->image->hasBeenTransformed());
        $this->assertSame($this->image, $this->image->hasBeenTransformed(true));
        $this->assertTrue($this->image->hasBeenTransformed());
        $this->assertSame($this->image, $this->image->hasBeenTransformed(false));
        $this->assertFalse($this->image->hasBeenTransformed());
    }
}
