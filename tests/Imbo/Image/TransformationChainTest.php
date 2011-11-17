<?php
/**
 * Imbo
 *
 * Copyright (c) 2011 Christer Edvartsen <cogo@starzinger.net>
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
 * @package Imbo
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */

namespace Imbo\Image;

/**
 * @package Imbo
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */
class TransformationChainTest extends \PHPUnit_Framework_TestCase {
    private $chain;

    public function setUp() {
        if (!class_exists('Imagine\Imagick\Imagine')) {
            $this->markTestSkipped('Imagine must be available to run this test');
        }

        $this->chain = new TransformationChain();
    }

    public function tearDown() {
        $this->chain = null;
    }

    public function testApplyTrasformationsToImage() {
        $image = $this->getMock('Imbo\Image\ImageInterface');

        $transformation = $this->getMock('Imbo\Image\Transformation\TransformationInterface');
        $transformation->expects($this->once())->method('applyToImage')->with($image);

        $this->assertSame($this->chain, $this->chain->add($transformation));
        $this->assertSame($this->chain, $this->chain->applyToImage($image));
    }

    public function testTransformImage() {
        $image = $this->getMock('Imbo\Image\ImageInterface');
        $transformation = $this->getMock('Imbo\Image\Transformation\TransformationInterface');
        $transformation->expects($this->once())->method('applyToImage')->with($image);

        $this->chain->transformImage($image, $transformation);
    }

    /**
     * Test that transformation methods are chainable
     */
    public function testChain() {
        $this->chain->border('fff', 1, 1)
                    ->compress(75)
                    ->crop(1, 2, 3, 4)
                    ->rotate(45, 'fff')
                    ->resize(100, 200)
                    ->thumbnail(10, 10, '000')
                    ->flipHorizontally()
                    ->flipVertically()
                    ->border('000', 2, 2);
    }

    public function testCountable() {
        $this->chain->border('fff', 1, 1);
        $this->assertSame(1, count($this->chain));

        $this->chain->compress(10);
        $this->assertSame(2, count($this->chain));
    }

    public function testIterator() {
        $this->chain->border('fff', 1, 2)->resize(1, 2)->thumbnail();

        $expectedClasses = array(
            'Imbo\Image\Transformation\Border',
            'Imbo\Image\Transformation\Resize',
            'Imbo\Image\Transformation\Thumbnail',
        );

        foreach ($this->chain as $key => $transformation) {
            $this->assertInstanceOf($expectedClasses[$key], $transformation);
        }
    }
}
