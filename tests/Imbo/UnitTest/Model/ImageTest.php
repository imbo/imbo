<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\Model;

use Imbo\Model\Image,
    Imbo\Image\Transformation\Transformation;

/**
 * @covers Imbo\Model\Image
 * @group unit
 * @group models
 */
class ImageTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Image
     */
    private $image;

    /**
     * Set up the model
     */
    public function setUp() {
        $this->image = new Image();
    }

    /**
     * Tear down the model
     */
    public function tearDown() {
        $this->image = null;
    }

    /**
     * @covers Imbo\Model\Image::setMetadata
     * @covers Imbo\Model\Image::getMetadata
     */
    public function testCanSetAndGetMetadata() {
        $this->assertNull($this->image->getMetadata());
        $data = array(
            'foo' => 'bar',
            'bar' => 'foo',
        );
        $this->assertSame($this->image, $this->image->setMetadata($data));
        $this->assertSame($data, $this->image->getMetadata());
    }

    /**
     * @covers Imbo\Model\Image::setMimeType
     * @covers Imbo\Model\Image::getMimeType
     */
    public function testCanSetAndGetMimeType() {
        $mimeType = 'image/png';
        $this->assertSame($this->image, $this->image->setMimeType($mimeType));
        $this->assertSame($mimeType, $this->image->getMimeType());
    }

    /**
     * @covers Imbo\Model\Image::setBlob
     * @covers Imbo\Model\Image::getBlob
     * @covers Imbo\Model\Image::getFilesize
     * @covers Imbo\Model\Image::setFilesize
     * @covers Imbo\Model\Image::getChecksum
     * @covers Imbo\Model\Image::setChecksum
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
     * @covers Imbo\Model\Image::setExtension
     * @covers Imbo\Model\Image::getExtension
     */
    public function testCanSetAndGetExtension() {
        $extension = 'png';
        $this->assertSame($this->image, $this->image->setExtension($extension));
        $this->assertSame($extension, $this->image->getExtension());
    }

    /**
     * @covers Imbo\Model\Image::setWidth
     * @covers Imbo\Model\Image::getWidth
     */
    public function testCanSetAndGetWidth() {
        $width = 123;
        $this->assertSame($this->image, $this->image->setWidth($width));
        $this->assertSame($width, $this->image->getWidth());
    }

    /**
     * @covers Imbo\Model\Image::setHeight
     * @covers Imbo\Model\Image::getHeight
     */
    public function testCanSetAndGetHeight() {
        $height = 234;
        $this->assertSame($this->image, $this->image->setHeight($height));
        $this->assertSame($height, $this->image->getHeight());
    }

    /**
     * @covers Imbo\Model\Image::setAddedDate
     * @covers Imbo\Model\Image::getAddedDate
     */
    public function testCanSetAndGetTheAddedDate() {
        $date = $this->getMock('DateTime');
        $this->assertNull($this->image->getAddedDate());
        $this->assertSame($this->image, $this->image->setAddedDate($date));
        $this->assertSame($date, $this->image->getAddedDate());
    }

    /**
     * @covers Imbo\Model\Image::setUpdatedDate
     * @covers Imbo\Model\Image::getUpdatedDate
     */
    public function testCanSetAndGetTheUpdatedDate() {
        $date = $this->getMock('DateTime');
        $this->assertNull($this->image->getUpdatedDate());
        $this->assertSame($this->image, $this->image->setUpdatedDate($date));
        $this->assertSame($date, $this->image->getUpdatedDate());
    }

    /**
     * @covers Imbo\Model\Image::setPublicKey
     * @covers Imbo\Model\Image::getPublicKey
     */
    public function testCanSetAndGetThePublicKey() {
        $this->assertNull($this->image->getPublicKey());
        $this->assertSame($this->image, $this->image->setPublicKey('christer'));
        $this->assertSame('christer', $this->image->getPublicKey());
    }

    /**
     * @covers Imbo\Model\Image::setImageIdentifier
     * @covers Imbo\Model\Image::getImageIdentifier
     */
    public function testCanSetAndGetTheImageIdentifier() {
        $this->assertNull($this->image->getImageIdentifier());
        $this->assertSame($this->image, $this->image->setImageIdentifier('identifier'));
        $this->assertSame('identifier', $this->image->getImageIdentifier());
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
     * @covers Imbo\Model\Image::supportedMimeType
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
     * @covers Imbo\Model\Image::getFileExtension
     * @dataProvider getFileExtensions
     */
    public function testCanGetAFileExtensionBasedOnAMimeType($type, $extension) {
        $this->assertSame($extension, Image::getFileExtension($type));
    }

    /**
     * @covers Imbo\Model\Image::hasBeenTransformed
     */
    public function testCanUpdateTransformationFlag() {
        $this->assertFalse($this->image->hasBeenTransformed());
        $this->assertSame($this->image, $this->image->hasBeenTransformed(true));
        $this->assertTrue($this->image->hasBeenTransformed());
        $this->assertSame($this->image, $this->image->hasBeenTransformed(false));
        $this->assertFalse($this->image->hasBeenTransformed());
    }
}
