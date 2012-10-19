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
        $blob = file_get_contents(FIXTURES_DIR . '/image.png');

        $image = $this->getMock('Imbo\Image\ImageInterface');
        $image->expects($this->any())->method('getBlob')->will($this->returnValue($blob));
        $image->expects($this->once())->method('setBlob')->with($this->isType('string'))->will($this->returnValue($image));
        $image->expects($this->once())->method('getWidth')->will($this->returnValue(665));
        $image->expects($this->once())->method('getHeight')->will($this->returnValue(463));
        $image->expects($this->once())->method('setWidth')->with($this->width)->will($this->returnValue($image));
        $image->expects($this->once())->method('setHeight')->with($this->height)->will($this->returnValue($image));
        $image->expects($this->once())->method('getExtension')->will($this->returnValue('png'));

        return $image;
    }
}
