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
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 * @covers Imbo\Model\Image
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
    public function testCanMarkIfTheImageHasBeenTransformedOrNot() {
        $this->assertFalse($this->image->hasBeenTransformed());
        $this->assertSame($this->image, $this->image->hasBeenTransformed(true));
        $this->assertTrue($this->image->hasBeenTransformed());
        $this->assertSame($this->image, $this->image->hasBeenTransformed(false));
        $this->assertFalse($this->image->hasBeenTransformed());
    }

    /**
     * @covers Imbo\Model\Image::transform
     * @covers Imbo\Model\Image::getTransformationHandler
     * @expectedException Imbo\Exception\TransformationException
     * @expectedExceptionMessage Unknown transformation: foobar
     * @expectedExceptionCode 400
     */
    public function testThrowsAnExceptionWhenTryingToApplyAnUnknownTransformation() {
        $this->image->transform('foobar');
    }

    /**
     * @covers Imbo\Model\Image::transform
     * @covers Imbo\Model\Image::getTransformationHandler
     * @expectedException Imbo\Exception\TransformationException
     * @expectedExceptionMessage Invalid image transformation: border
     * @expectedExceptionCode 500
     */
    public function testThrowsAnExceptionWhenTryingToApplyAnInvalidTransformation() {
        $this->image->setTransformationHandler('border', 'stdClass');
        $this->image->transform('border');
    }

    public function getTransformations() {
        return array(
            'class name as string' => array(
                'border', __NAMESPACE__ . '\Border', array(), 'a:0:{}'
            ),
            'class name as string with params' => array(
                'border', __NAMESPACE__ . '\Border', array('width' => 100), 'a:1:{s:5:"width";i:100;}'
            ),
            'transformation as closure' => array(
                'border', function() { return new Border(); }, array(), 'a:0:{}'
            ),
            'transformation as closure with params' => array(
                'border', function() { return new Border(); }, array('width' => 100), 'a:1:{s:5:"width";i:100;}'
            ),
        );
    }

    /**
     * @dataProvider getTransformations
     * @covers Imbo\Model\Image::transform
     * @covers Imbo\Model\Image::getTransformationHandler
     */
    public function testCanTransformAnImageUsingImageTransformations($key, $transformation, $params, $output) {
        $this->image->setTransformationHandler($key, $transformation);
        $this->expectOutputString($output);
        $this->image->transform($key, $params);
    }

    /**
     * @covers Imbo\Model\Image::transform
     * @covers Imbo\Model\Image::getTransformationHandler
     */
    public function testCanTransformAnImageUsingPresets() {
        $this->image->setTransformationHandler('border1', __NAMESPACE__ . '\Border');
        $this->image->setTransformationHandler('border2', __NAMESPACE__ . '\Border');
        $this->image->setTransformationHandler('border', array(
            'border1',
            'border2' => array(
                'width' => 5,
            ),
        ));
        $this->expectOutputString('a:1:{s:5:"width";i:1;}a:1:{s:5:"width";i:5;}');
        $this->image->transform('border', array('width' => 1));
    }

    /**
     * @covers Imbo\Model\Image::transform
     * @covers Imbo\Model\Image::setImageReader
     */
    public function testStoresAnImageReaderInImageReaderAwareTransformations() {
        $reader = $this->getMockBuilder('Imbo\Storage\ImageReader')->disableOriginalConstructor()->getMock();
        $transformation = $this->getMock('Imbo\Image\Transformation\Watermark');
        $transformation->expects($this->once())->method('setImageReader')->with($reader);

        $this->image->setImageReader($reader)
                    ->setTransformationHandler('watermark', function() use ($transformation) { return $transformation; })
                    ->transform('watermark');
    }
}

class Border extends Transformation {
    public function applyToImage(Image $image, array $params = array()) {
        echo serialize($params);
    }
}
