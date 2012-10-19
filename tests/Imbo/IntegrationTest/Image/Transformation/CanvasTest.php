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

namespace Imbo\IntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\Canvas,
    Imagick;

/**
 * @package TestSuite\IntegrationTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\Image\Transformation\Canvas
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
        return new Canvas(array(
            'width' => $this->width,
            'height' => $this->height,
            'mode' => 'free',
            'x' => 10,
            'y' => 20,
            'bg' => 'bf1942',
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedName() {
        return 'canvas';
    }

    /**
     * {@inheritdoc}
     * @covers Imbo\Image\Transformation\Canvas::applyToImage
     */
    protected function getImageMock() {
        $image = $this->getMock('Imbo\Image\ImageInterface');
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
     * @covers Imbo\Image\Transformation\Canvas::applyToImage
     */
    public function testApplyToImageWithDifferentParameters($width, $height, $mode = 'free', $resultingWidth = 665, $resultingHeight = 463) {
        $image = $this->getMock('Imbo\Image\ImageInterface');
        $image->expects($this->any())->method('getBlob')->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/image.png')));
        $image->expects($this->any())->method('getWidth')->will($this->returnValue(665));
        $image->expects($this->any())->method('getHeight')->will($this->returnValue(463));
        $image->expects($this->any())->method('getExtension')->will($this->returnValue('png'));
        $image->expects($this->once())->method('setBlob')->with($this->isType('string'))->will($this->returnValue($image));
        $image->expects($this->once())->method('setWidth')->with($resultingWidth)->will($this->returnValue($image));
        $image->expects($this->once())->method('setHeight')->with($resultingHeight)->will($this->returnValue($image));

        $canvas = new Canvas($width, $height, $mode);
        $canvas->applyToImage($image);
    }
}
