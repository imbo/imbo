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
    Imbo\EventManager\Event,
    Imagick;

/**
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Test suite\Integration tests
 * @covers Imbo\Image\Transformation\Watermark
 */
class WatermarkTest extends \PHPUnit_Framework_TestCase {
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
        $this->transformation = new Watermark();
    }

    /**
     * Tear down the transformation
     */
    public function tearDown() {
        $this->transformation = null;
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
        $event = new Event();
        $event->setArguments(array(
            'image' => $image,
            'params' => array(),
        ));

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

        $storage = $this->getMock('Imbo\Storage\StorageInterface');
        $storage->expects($this->once())
                ->method('getImage')
                ->with('publickey', 'non-existant')
                ->will($this->throwException($e));

        $request = $this->getMock('Imbo\Http\Request\Request');
        $request->expects($this->once())->method('getPublicKey')->will($this->returnValue('publickey'));

        $event = new Event();
        $event->setArguments(array(
            'image' => $image,
            'params' => array(
                'img' => 'non-existant',
            ),
            'storage' => $storage,
            'request' => $request,
        ));

        $this->transformation->transform($event);
    }

    public function getParamsForWatermarks() {
        return array(
            'top left with default watermark and width' => array(
                array(
                    'width' => 200,
                ),
                array(
                    array('x' => 0, 'y' => 0, 'colors' => array(89, 142, 4)),
                    array('x' => 200, 'y' => 50, 'colors' => array(0, 0, 0)),
                ),
            ),
            'top left with default watermark and height' => array(
                array(
                    'height' => 50,
                ),
                array(
                    array('x' => 0, 'y' => 0, 'colors' => array(89, 142, 4)),
                    array('x' => 200, 'y' => 50, 'colors' => array(0, 0, 0)),
                ),
            ),
            'bottom right with custom watermark and offset' => array(
                array(
                    'height' => 50,
                    'img' => $this->watermarkImg,
                    'x' => -5,
                    'y' => -5,
                    'position' => 'bottom-right',
                ),
                array(
                    array('x' => 0, 'y' => 0, 'colors' => array(255, 255, 255)),
                    array('x' => $this->width - 1, 'y' => $this->height - 1, 'colors' => array(109, 106, 104)),
                    array('x' => $this->width - 6, 'y' => $this->height - 6, 'colors' => array(37, 93, 14)),
                ),
            ),
            'bottom left with custom watermark and offset' => array(
                array(
                    'height' => 50,
                    'img' => $this->watermarkImg,
                    'x' => 5,
                    'y' => -5,
                    'position' => 'bottom-left',
                ),
                array(
                    array('x' => 0, 'y' => 0, 'colors' => array(255, 255, 255)),
                    array('x' => 0, 'y' => $this->height - 1, 'colors' => array(109, 106, 104)),
                    array('x' => 0 + 6, 'y' => $this->height - 6, 'colors' => array(89, 142, 4)),
                ),
            ),
            'center with custom watermark' => array(
                array(
                    'height' => 50,
                    'img' => $this->watermarkImg,
                    'position' => 'center',
                ),
                array(
                    array('x' => 0, 'y' => 0, 'colors' => array(255, 255, 255)),
                    array('x' => 0, 'y' => $this->height - 1, 'colors' => array(109, 106, 104)),
                    array('x' => floor($this->width / 2) - 84, 'y' => floor($this->height / 2) - 18, 'colors' => array(89, 142, 4)),
                ),
            ),
            'custom watermark with no params' => array(
                array(
                    'img' => $this->watermarkImg,
                ),
                array(
                    array('x' => 0, 'y' => 0, 'colors' => array(89, 142, 4)),
                    array('x' => $this->width - 1, 'y' => 0, 'colors' => array(152, 196, 0)),
                    array('x' => 0, 'y' => $this->height - 1, 'colors' => array(109, 106, 104)),
                    array('x' => $this->width - 1, 'y' => $this->height - 1, 'colors' => array(109, 106, 104)),
                ),
            ),
        );
    }

    /**
     * @dataProvider getParamsForWatermarks
     */
    public function testApplyToImageTopLeftWithOnlyWidthAndDefaultWatermark($params, $colors) {
        $image = $this->getImageInstance();

        $this->transformation->setDefaultImage($this->watermarkImg);

        $expectedWatermark = $this->watermarkImg;

        if (isset($params['img'])) {
            $expectedWatermark = $params['img'];
        }

        $storage = $this->getMock('Imbo\Storage\StorageInterface');
        $storage->expects($this->once())
                ->method('getImage')
                ->with('publickey', $expectedWatermark)
                ->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/logo-horizontal.png')));

        $request = $this->getMock('Imbo\Http\Request\Request');
        $request->expects($this->once())->method('getPublicKey')->will($this->returnValue('publickey'));

        $event = new Event();
        $event->setArguments(array(
            'image' => $image,
            'params' => $params,
            'storage' => $storage,
            'request' => $request,
        ));

        $this->transformation->transform($event);

        foreach ($colors as $c) {
            $this->verifyColor($image, $c['x'], $c['y'], $c['colors']);
        }
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
