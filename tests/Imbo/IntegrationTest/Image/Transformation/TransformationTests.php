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

use Imbo\Image\Transformation\TransformationInterface,
    Imbo\Image\ImageInterface;

/**
 * @package TestSuite\IntegrationTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
abstract class TransformationTests extends \PHPUnit_Framework_TestCase {
    /**
     * Test cases must implement this method and return a configured instande of the transformation
     * they are testing. This transformation instance will be used for the tests in this base test
     * case
     *
     * @return TransformationInterface
     */
    abstract protected function getTransformation();

    /**
     * @return string
     */
    abstract protected function getExpectedName();

    /**
     * Get the image mock used in the simple testApplyToImage
     *
     * @return ImageInterface
     */
    abstract protected function getImageMock();

    /**
     * Make sure we have Imagick available
     */
    public function setUp() {
        if (!class_exists('Imagick')) {
            $this->markTestSkipped('Imagick must be available to run this test');
        }
    }

    /**
     * Make sure the transformation returns the expected name
     *
     * @covers Imbo\Image\Transformation\Transformation::getName
     */
    public function testGetName() {
        $this->assertSame($this->getTransformation()->getName(), $this->getExpectedName());
    }

    /**
     * Simply apply the current transformation to an image instance
     *
     * The transformation instance returned from getTransformation() will be used
     */
    public function testSimpleApplyToImage() {
        $image = $this->getImageMock();

        $this->getTransformation()->applyToImage($image);
    }

    /**
     * @expectedException Imbo\Exception\TransformationException
     */
    public function testApplyToImageWithUnknownImageFormat() {
        $image = $this->getMock('Imbo\Image\ImageInterface');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue('some string'));
        $image->expects($this->any())->method('getWidth')->will($this->returnValue(1600));
        $image->expects($this->any())->method('getHeight')->will($this->returnValue(900));

        $this->getTransformation()->applyToImage($image);
    }
}
