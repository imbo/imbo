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
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\UnitTest\Image;

use Imbo\Image\ImagePreparation;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\Image\ImagePreparation
 */
class ImagePreparationTest extends \PHPUnit_Framework_TestCase {
    private $preparation;
    private $request;
    private $image;

    public function setUp() {
        $this->request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $this->image = $this->getMock('Imbo\Image\ImageInterface');
        $this->prepare = new ImagePreparation();
    }

    public function tearDown() {
        $this->preparation = null;
        $this->request = null;
        $this->image = null;
    }

    /**
     * @covers Imbo\Image\ImagePreparation::prepareImage
     * @expectedException Imbo\Exception\ImageException
     * @expectedExceptionMessage No image attached
     * @expectedExceptionCode 400
     */
    public function testPrepareImageWithMissingImageData() {
        $this->request->expects($this->once())->method('getRawData')->will($this->returnValue(''));

        $this->prepare->prepareImage($this->request, $this->image);
    }

    /**
     * @covers Imbo\Image\ImagePreparation::prepareImage
     * @expectedException Imbo\Exception\ImageException
     * @expectedExceptionMessage Hash mismatch
     * @expectedExceptionCode 400
     */
    public function testPrepareImageWithHashMismatch() {
        $this->request->expects($this->once())->method('getRawData')->will($this->returnValue(file_get_contents(FIXTURES_DIR . '/image.png')));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('asd'));

        $this->prepare->prepareImage($this->request, $this->image);
    }

    /**
     * @covers Imbo\Image\ImagePreparation::prepareImage
     * @expectedException Imbo\Exception\ImageException
     * @expectedExceptionMessage Unsupported image type
     * @expectedExceptionCode 415
     */
    public function testPrepareImageWithUnsupportedImageType() {
        $this->request->expects($this->once())->method('getRawData')->will($this->returnValue(file_get_contents(__FILE__)));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue(md5_file(__FILE__)));

        $this->prepare->prepareImage($this->request, $this->image);
    }

    /**
     * @covers Imbo\Image\ImagePreparation::prepareImage
     * @expectedException Imbo\Exception\ImageException
     * @expectedExceptionMessage Broken image
     * @expectedExceptionCode 415
     */
    public function testPrepareImageWithBrokenImage() {
        $filePath = FIXTURES_DIR . '/broken-image.jpg';

        $this->request->expects($this->once())->method('getRawData')->will($this->returnValue(file_get_contents($filePath)));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue(md5_file($filePath)));

        $this->prepare->prepareImage($this->request, $this->image);
    }

    /**
     * @covers Imbo\Image\ImagePreparation::prepareImage
     */
    public function testSuccessfulPrepareImage() {
        $imagePath = FIXTURES_DIR . '/image.png';
        $imageData = file_get_contents($imagePath);
        $imageIdentifier = md5($imageData);

        $this->request->expects($this->once())->method('getRawData')->will($this->returnValue($imageData));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($imageIdentifier));

        $this->image->expects($this->once())->method('setMimeType')->with('image/png')->will($this->returnValue($this->image));
        $this->image->expects($this->once())->method('setExtension')->with('png')->will($this->returnValue($this->image));
        $this->image->expects($this->once())->method('setBlob')->with($imageData)->will($this->returnValue($this->image));
        $this->image->expects($this->once())->method('setWidth')->with(665)->will($this->returnValue($this->image));
        $this->image->expects($this->once())->method('setHeight')->with(463)->will($this->returnValue($this->image));

        $this->prepare->prepareImage($this->request, $this->image);
    }
}
