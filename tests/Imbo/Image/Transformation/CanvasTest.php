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
 * @package Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\Image\Transformation;

use Imagick;

/**
 * @package Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\Image\Transformation\Canvas
 */
class CanvasTest extends TransformationTests {
    protected function getTransformation() {
        return new Canvas(100, 100, 'free', 10, 10, '000');
    }

    /**
     * @covers Imbo\Image\Transformation\Canvas::applyToImage
     */
    public function testApplyToImage() {
        $mode = 'free';
        $width = 700;
        $height = 500;
        $x = 10;
        $y = 20;
        $bg = 'bf1942';
        $blob = file_get_contents(__DIR__ . '/../../_files/image.png');

        $image = $this->getMock('Imbo\Image\ImageInterface');
        $image->expects($this->any())->method('getBlob')->will($this->returnValue($blob));
        $image->expects($this->once())->method('setBlob')->with($this->isType('string'))->will($this->returnValue($image));
        $image->expects($this->once())->method('getWidth')->will($this->returnValue(665));
        $image->expects($this->once())->method('getHeight')->will($this->returnValue(463));
        $image->expects($this->once())->method('setWidth')->with($width)->will($this->returnValue($image));
        $image->expects($this->once())->method('setHeight')->with($height)->will($this->returnValue($image));
        $image->expects($this->once())->method('getExtension')->will($this->returnValue('png'));

        $transformation = new Canvas($width, $height, $mode, $x, $y, $bg);
        $transformation->applyToImage($image);

        $imagick = new Imagick();
        $imagick->readImageBlob($image->getBlob());

        $canvasColor = $imagick->getImagePixelColor(695, 495);
        $windowColor = $imagick->getImagePixelColor(14, 69);
        $this->assertSame('rgb(109,106,104)', $canvasColor->getColorAsString());
        $this->assertSame('rgb(0,0,0)',       $windowColor->getColorAsString());
    }
}
