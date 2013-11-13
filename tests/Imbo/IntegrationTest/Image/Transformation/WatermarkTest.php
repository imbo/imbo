<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\IntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\Watermark,
    Imbo\Exception\StorageException,
    Imbo\Model\Image,
    Imagick;

/**
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Test suite\Integration tests
 * @covers Imbo\Image\Transformation\Watermark
 */
class WatermarkTest extends TransformationTests {
    /**
     * @var int
     */
    private $width = 665;

    /**
     * @var int
     */
    private $height = 463;

    /**
     * @var string
     */
    private $watermarkImg = 'f5f7851c40e2b76a01af9482f67bbf3f';

    /**
     * Set up the transformation
     */
    public function setUp() {
        parent::setUp();

        $this->imageReader = $this->getMockBuilder('Imbo\Storage\ImageReader')
                                  ->disableOriginalConstructor()
                                  ->getMock();

        $this->imageReader->expects($this->any())
                          ->method('getImage')
                          ->with($this->watermarkImg)
                          ->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/logo-horizontal.png')));

        $this->transformation = new Watermark();
        $this->transformation->setImageReader($this->imageReader);
    }

    /**
     * Tear down the transformation
     */
    public function tearDown() {
        $this->transformation = null;
        $this->imageReader = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return $this->transformation;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultParams() {
        return array(
            'width'    => $this->width,
            'height'   => $this->height,
            'position' => 'center',
            'x'        => 10,
            'y'        => 20,
            'img'      => $this->watermarkImg,
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getImageMock() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->any())->method('getBlob')->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/image.png')));
        $image->expects($this->once())->method('setBlob')->with($this->isType('string'))->will($this->returnValue($image));
        $image->expects($this->once())->method('getWidth')->will($this->returnValue(665));
        $image->expects($this->once())->method('getHeight')->will($this->returnValue(463));

        return $image;
    }

    /**
     * Get an actual image instance, populate with the binary contents of the given image
     *
     * @param  string $file Filename of the image to load
     * @param  integer $width Width of the image
     * @param  integer $height Height of the image
     * @return Image
     */
    protected function getImageInstance($file = null, $width = 665, $height = 463) {
        $image = new Image();
        $image->setBlob(file_get_contents($file ?: (FIXTURES_DIR . '/image.png')));
        $image->setWidth($width);
        $image->setHeight($height);

        return $image;
    }

    /**
     * @expectedException Imbo\Exception\TransformationException
     * @expectedExceptionCode 400
     * @expectedExceptionMessage You must specify an image identifier to use for the watermark
     */
    public function testTransformThrowsExceptionIfNoImageSpecified() {
        $image = $this->getImageInstance();
        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('image')->will($this->returnValue($image));
        $event->expects($this->at(1))->method('getArgument')->with('params')->will($this->returnValue(array()));

        $this->transformation->transform($event);
    }

    /**
     * @expectedException Imbo\Exception\TransformationException
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Watermark image not found
     */
    public function testApplyToImageThrowsExceptionIfSpecifiedImageIsNotFound() {
        $image = $this->getImageInstance();
        $e = new StorageException('File not found', 404);

        $this->imageReader->expects($this->once())
                          ->method('getImage')
                          ->with('non-existant')
                          ->will($this->throwException($e));

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('image')->will($this->returnValue($image));
        $event->expects($this->at(1))->method('getArgument')->with('params')->will($this->returnValue(array(
            'img' => 'non-existant',
        )));

        $this->transformation->transform($event);
    }

    public function testApplyToImageTopLeftWithOnlyWidthAndDefaultWatermark() {
        $image = $this->getImageInstance();

        $this->transformation->setDefaultImage($this->watermarkImg);

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('image')->will($this->returnValue($image));
        $event->expects($this->at(1))->method('getArgument')->with('params')->will($this->returnValue(array(
            'width' => 200,
        )));

        $this->transformation->transform($event);

        $this->verifyColor($image, 0, 0, array(89, 142, 4));
        $this->verifyColor($image, 200, 50, array(0, 0, 0));
    }

    public function testApplyToImageTopLeftWithOnlyHeightAndDefaultWatermark() {
        $image = $this->getImageInstance();

        $this->transformation->setDefaultImage($this->watermarkImg);

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('image')->will($this->returnValue($image));
        $event->expects($this->at(1))->method('getArgument')->with('params')->will($this->returnValue(array(
            'height' => 50,
        )));

        $this->transformation->transform($event);

        $this->verifyColor($image, 0, 0, array(89, 142, 4));
        $this->verifyColor($image, 200, 50, array(0, 0, 0));
    }

    public function testApplyToImageBottomRightWithOffsetAndSpecificWatermark() {
        $image = $this->getImageInstance();

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('image')->will($this->returnValue($image));
        $event->expects($this->at(1))->method('getArgument')->with('params')->will($this->returnValue(array(
            'height'   => 50,
            'img'      => $this->watermarkImg,
            'x'        => -5,
            'y'        => -5,
            'position' => 'bottom-right',
        )));

        $this->transformation->transform($event);

        $this->verifyColor($image, 0, 0, array(255, 255, 255));
        $this->verifyColor($image, $this->width - 1, $this->height - 1, array(109, 106, 104));
        $this->verifyColor($image, $this->width - 6, $this->height - 6, array(37, 93, 14));
    }

    public function testApplyToImageBottomLeftWithOffsetAndSpecificWatermark() {
        $image = $this->getImageInstance();

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('image')->will($this->returnValue($image));
        $event->expects($this->at(1))->method('getArgument')->with('params')->will($this->returnValue(array(
            'height'   => 50,
            'img'      => $this->watermarkImg,
            'x'        => 5,
            'y'        => -5,
            'position' => 'bottom-left',
        )));

        $this->transformation->transform($event);

        $this->verifyColor($image, 0, 0, array(255, 255, 255));
        $this->verifyColor($image, 0, $this->height - 1, array(109, 106, 104));
        $this->verifyColor($image, 0 + 6, $this->height - 6, array(89, 142, 4));
    }

    public function testApplyToImageCenterWithSpecificWatermark() {
        $image = $this->getImageInstance();

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('image')->will($this->returnValue($image));
        $event->expects($this->at(1))->method('getArgument')->with('params')->will($this->returnValue(array(
            'height'   => 50,
            'img'      => $this->watermarkImg,
            'position' => 'center',
        )));

        $this->transformation->transform($event);

        $centerX = floor($this->width / 2);
        $centerY = floor($this->height / 2);

        $this->verifyColor($image, 0, 0, array(255, 255, 255));
        $this->verifyColor($image, 0, $this->height - 1, array(109, 106, 104));

        $this->verifyColor($image, $centerX - 84, $centerY - 18, array(89, 142, 4));
    }

    public function testApplyToImageWithoutWidthOrHeight() {
        $image = $this->getImageInstance();

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('image')->will($this->returnValue($image));
        $event->expects($this->at(1))->method('getArgument')->with('params')->will($this->returnValue(array(
            'img' => $this->watermarkImg,
        )));

        $this->transformation->transform($event);

        $this->verifyColor($image, 0, 0, array(89, 142, 4));
        $this->verifyColor($image, $this->width - 1, 0, array(152, 196, 0));
        $this->verifyColor($image, 0, $this->height - 1, array(109, 106, 104));
        $this->verifyColor($image, $this->width - 1, $this->height - 1, array(109, 106, 104));
    }

    /**
     * Verifies that the given image has a pixel with the given color value at the given position
     *
     * @param Image $img Image to read contents from
     * @param integer $x X position to check
     * @param integer $y Y position to check
     * @param array $expectedRgb Expected color value, in RGB format, as array
     */
    protected function verifyColor($img, $x, $y, $expectedRgb) {
        // Do assertion comparison on the color values
        $imagick = new Imagick();
        $imagick->readImageBlob($img->getBlob());

        $pixelValue = $imagick->getImagePixelColor($x, $y)->getColorAsString();

        $this->assertStringEndsWith('rgb(' . implode(',', $expectedRgb) . ')', $pixelValue);
    }
}
