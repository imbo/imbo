<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Model;

use Imbo\Model\Image,
    Imbo\Image\Transformation\Transformation,
    DateTime;

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
        $data = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];
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
     * @covers Imbo\Model\Image::setUser
     * @covers Imbo\Model\Image::getUser
     */
    public function testCanSetAndGetTheUser() {
        $this->assertNull($this->image->getUser());
        $this->assertSame($this->image, $this->image->setUser('christer'));
        $this->assertSame('christer', $this->image->getUser());
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
    public function getSupportedMimeTypes() {
        return [
            ['image/png', true],
            ['image/x-png', true],
            ['image/jpeg', true],
            ['image/x-jpeg', true],
            ['image/gif', true],
            ['image/x-gif', true],
            ['image/jpg', false],
        ];
    }

    /**
     * @covers Imbo\Model\Image::supportedMimeType
     * @dataProvider getSupportedMimeTypes
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
        return [
            ['image/png', 'png'],
            ['image/jpeg', 'jpg'],
            ['image/gif', 'gif'],
            ['image/x-png', 'png'],
            ['image/x-jpeg', 'jpg'],
            ['image/x-gif', 'gif'],
            ['image/jpg', false],
        ];
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

    public function testCanSetAndGetTheOriginalChecksum() {
        $checksum = md5(__FILE__);
        $this->assertSame($this->image, $this->image->setOriginalChecksum($checksum));
        $this->assertSame($checksum, $this->image->getOriginalChecksum());
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getMimeTypes() {
        return [
            ['image/jpeg', 'image/jpeg'],
            ['image/png', 'image/png'],
            ['image/gif', 'image/gif'],
            ['image/x-jpeg', 'image/jpeg'],
            ['image/x-png', 'image/png'],
            ['image/x-gif', 'image/gif'],
        ];
    }

    /**
     * @dataProvider getMimeTypes
     */
    public function testSetsTheCorrectMimeTypeWhenAMappedOneIsUsed($set, $get) {
        $this->image->setMimeType($set);
        $this->assertSame($get, $this->image->getMimeType());
    }

    /**
     * @covers Imbo\Model\Image::getData
     */
    public function testGetData() {
        $metadata = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $mimeType = 'image/png';
        $blob = 'some string';
        $filesize = strlen($blob);
        $checksum = md5($blob);
        $extension = 'png';
        $width = 123;
        $height = 234;
        $added = new DateTime();
        $updated = new DateTime();
        $user = 'christer';
        $identifier = 'identifier';

        $this->image
            ->setMetadata($metadata)
            ->setMimeType($mimeType)
            ->setBlob($blob)
            ->setExtension($extension)
            ->setWidth($width)
            ->setHeight($height)
            ->setAddedDate($added)
            ->setUpdatedDate($updated)
            ->setUser($user)
            ->setImageIdentifier($identifier)
            ->hasBeenTransformed(true)
            ->setOriginalChecksum($checksum);

        $this->assertSame([
            'filesize' => $filesize,
            'mimeType' => $mimeType,
            'extension' => $extension,
            'metadata' => $metadata,
            'width' => $width,
            'height' => $height,
            'addedDate' => $added,
            'updatedDate' => $updated,
            'user' => $user,
            'imageIdentifier' => $identifier,
            'checksum' => $checksum,
            'originalChecksum' => $checksum,
            'hasBeenTransformed' => true,
        ], $this->image->getData());
    }
}
