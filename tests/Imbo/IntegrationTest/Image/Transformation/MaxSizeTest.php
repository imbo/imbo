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

use Imbo\Image\Transformation\MaxSize;

/**
 * @package TestSuite\IntegrationTests
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\Image\Transformation\MaxSize
 */
class MaxSizeTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new MaxSize(array('width' => 200, 'height' => 100));
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedName() {
        return 'maxsize';
    }

    /**
     * {@inheritdoc}
     * @covers Imbo\Image\Transformation\MaxSize::applyToImage
     */
    protected function getImageMock() {
        $image = $this->getMock('Imbo\Image\ImageInterface');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/image.png')));
        $image->expects($this->once())->method('setBlob')->with($this->isType('string'))->will($this->returnValue($image));
        $image->expects($this->once())->method('getWidth')->will($this->returnValue(665));
        $image->expects($this->once())->method('getHeight')->will($this->returnValue(463));
        $image->expects($this->once())->method('setWidth')->with(144)->will($this->returnValue($image));
        $image->expects($this->once())->method('setHeight')->with(100)->will($this->returnValue($image));

        return $image;
    }

    /**
     * @covers Imbo\Image\Transformation\MaxSize::applyToImage
     */
    public function testApplyToImageWithOnlyWidth() {
        $image = $this->getMock('Imbo\Image\ImageInterface');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/image.png')));
        $image->expects($this->once())->method('setBlob')->with($this->isType('string'))->will($this->returnValue($image));
        $image->expects($this->once())->method('getWidth')->will($this->returnValue(665));
        $image->expects($this->once())->method('getHeight')->will($this->returnValue(463));
        $image->expects($this->once())->method('setWidth')->with(200)->will($this->returnValue($image));
        $image->expects($this->once())->method('setHeight')->with(139)->will($this->returnValue($image));

        $transformation = new MaxSize(array('width' => 200));
        $transformation->applyToImage($image);
    }

    /**
     * @covers Imbo\Image\Transformation\MaxSize::applyToImage
     */
    public function testApplyToImageWithOnlyHeight() {
        $image = $this->getMock('Imbo\Image\ImageInterface');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/image.png')));
        $image->expects($this->once())->method('setBlob')->with($this->isType('string'))->will($this->returnValue($image));
        $image->expects($this->once())->method('getWidth')->will($this->returnValue(665));
        $image->expects($this->once())->method('getHeight')->will($this->returnValue(463));
        $image->expects($this->once())->method('setWidth')->with(287)->will($this->returnValue($image));
        $image->expects($this->once())->method('setHeight')->with(200)->will($this->returnValue($image));

        $transformation = new MaxSize(array('height' => 200));
        $transformation->applyToImage($image);
    }

    /**
     * @covers Imbo\Image\Transformation\MaxSize::applyToImage
     */
    public function testApplyToTallImage() {
        $image = $this->getMock('Imbo\Image\ImageInterface');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/tall-image.png')));
        $image->expects($this->once())->method('setBlob')->with($this->isType('string'))->will($this->returnValue($image));
        $image->expects($this->once())->method('getWidth')->will($this->returnValue(463));
        $image->expects($this->once())->method('getHeight')->will($this->returnValue(665));
        $image->expects($this->once())->method('setWidth')->with(70)->will($this->returnValue($image));
        $image->expects($this->once())->method('setHeight')->with(100)->will($this->returnValue($image));

        $transformation = new MaxSize(array('width' => 200, 'height' => 100));
        $transformation->applyToImage($image);
    }

    /**
     * @covers Imbo\Image\Transformation\MaxSize::applyToImage
     */
    public function testApplyToImageSmallerThanParams() {
        $image = $this->getMock('Imbo\Image\ImageInterface');
        $image->expects($this->once())->method('getWidth')->will($this->returnValue(463));
        $image->expects($this->once())->method('getHeight')->will($this->returnValue(665));
        $image->expects($this->never())->method('setBlob');
        $image->expects($this->never())->method('setWidth');
        $image->expects($this->never())->method('setHeight');

        $transformation = new MaxSize(array('width' => 1000, 'height' => 1000));
        $transformation->applyToImage($image);
    }
}
