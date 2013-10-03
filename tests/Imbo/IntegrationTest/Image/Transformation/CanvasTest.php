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

use Imbo\Image\Transformation\Canvas,
    Imagick;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Integration tests
 */
class CanvasTest extends TransformationTests {
    /**
     * @var int
     */
    private $width = 700;

    /**
     * @var int
     */
    private $height = 500;

    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new Canvas();
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultParams() {
        return array(
            'width' => $this->width,
            'height' => $this->height,
            'mode' => 'free',
            'x' => 10,
            'y' => 20,
            'bg' => 'bf1942',
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
        $image->expects($this->once())->method('setWidth')->with($this->width)->will($this->returnValue($image));
        $image->expects($this->once())->method('setHeight')->with($this->height)->will($this->returnValue($image));
        $image->expects($this->once())->method('getExtension')->will($this->returnValue('png'));

        return $image;
    }

    /**
     * Fetch different canvas parameters
     *
     * @return array[]
     */
    public function getCanvasParameters() {
        return array(
            // free mode with only width
            array(1000, null, 'free', 1000, 463),

            // free mode with only height
            array(null, 1000, 'free', 665, 1000),

            // free mode where both sides are smaller than the original
            array(200, 200, 'free', 200, 200),

            // free mode where height is smaller than the original
            array(1000, 200, 'free', 1000, 200),

            // free mode where width is smaller than the original
            array(200, 1000, 'free', 200, 1000),

            // center, center-x and center-y modes
            array(1000, 1000, 'center', 1000, 1000),
            array(1000, 1000, 'center-x', 1000, 1000),
            array(1000, 1000, 'center-y', 1000, 1000),

            // center, center-x and center-y modes where one of the sides are smaller than the
            // original
            array(1000, 200, 'center', 1000, 200),
            array(200, 1000, 'center', 200, 1000),
            array(1000, 200, 'center-x', 1000, 200),
            array(1000, 200, 'center-y', 1000, 200),

            // center, center-x and center-y modes where both sides are smaller than the original
            array(200, 200, 'center', 200, 200),
            array(200, 200, 'center-x', 200, 200),
            array(200, 200, 'center-y', 200, 200),
        );
    }

    /**
     * @dataProvider getCanvasParameters
     */
    public function testApplyToImageWithDifferentParameters($width, $height, $mode = 'free', $resultingWidth = 665, $resultingHeight = 463) {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->any())->method('getBlob')->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/image.png')));
        $image->expects($this->any())->method('getWidth')->will($this->returnValue(665));
        $image->expects($this->any())->method('getHeight')->will($this->returnValue(463));
        $image->expects($this->any())->method('getExtension')->will($this->returnValue('png'));
        $image->expects($this->once())->method('setBlob')->with($this->isType('string'))->will($this->returnValue($image));
        $image->expects($this->once())->method('setWidth')->with($resultingWidth)->will($this->returnValue($image));
        $image->expects($this->once())->method('setHeight')->with($resultingHeight)->will($this->returnValue($image));

        $this->getTransformation()->applyToImage($image, array('width' => $width, 'height' => $height, 'mode' => $mode));
    }
}
