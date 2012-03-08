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

namespace Imbo\Image;

/**
 * @package Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\Image\TransformationChain
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

    /**
     * @covers Imbo\Image\TransformationChain::applyToImage
     */
    public function testApplyTrasformationsToImage() {
        $image = $this->getMock('Imbo\Image\ImageInterface');

        $transformation = $this->getMock('Imbo\Image\Transformation\TransformationInterface');
        $transformation->expects($this->once())->method('applyToImage')->with($image);

        $this->assertSame($this->chain, $this->chain->add($transformation));
        $this->assertSame($this->chain, $this->chain->applyToImage($image));
    }

    /**
     * @covers Imbo\Image\TransformationChain::transformImage
     */
    public function testTransformImage() {
        $image = $this->getMock('Imbo\Image\ImageInterface');
        $transformation = $this->getMock('Imbo\Image\Transformation\TransformationInterface');
        $transformation->expects($this->once())->method('applyToImage')->with($image);

        $this->chain->transformImage($image, $transformation);
    }

    /**
     * Test that transformation methods are chainable
     *
     * @covers Imbo\Image\TransformationChain::border
     * @covers Imbo\Image\TransformationChain::canvas
     * @covers Imbo\Image\TransformationChain::compress
     * @covers Imbo\Image\TransformationChain::crop
     * @covers Imbo\Image\TransformationChain::flipHorizontally
     * @covers Imbo\Image\TransformationChain::flipVertically
     * @covers Imbo\Image\TransformationChain::maxSize
     * @covers Imbo\Image\TransformationChain::resize
     * @covers Imbo\Image\TransformationChain::rotate
     * @covers Imbo\Image\TransformationChain::thumbnail
     */
    public function testChain() {
        $this->assertSame($this->chain,
            $this->chain->border('fff', 1, 1)
                        ->canvas(100, 100, 10, 10, '000')
                        ->compress(75)
                        ->crop(1, 2, 3, 4)
                        ->flipHorizontally()
                        ->flipVertically()
                        ->maxSize(100, 200)
                        ->resize(100, 200)
                        ->rotate(45, 'fff')
                        ->thumbnail(10, 10, '000')
        );
    }

    /**
     * @covers Imbo\Image\TransformationChain::count
     */
    public function testCountable() {
        $this->chain->border('fff', 1, 1);
        $this->assertSame(1, count($this->chain));

        $this->chain->compress(10);
        $this->assertSame(2, count($this->chain));
    }

    /**
     * @covers Imbo\Image\TransformationChain::add
     * @covers Imbo\Image\TransformationChain::rewind
     * @covers Imbo\Image\TransformationChain::current
     * @covers Imbo\Image\TransformationChain::key
     * @covers Imbo\Image\TransformationChain::next
     * @covers Imbo\Image\TransformationChain::valid
     */
    public function testIterator() {
        $this->chain->add($this->getMock('Imbo\Image\Transformation\Border'))
                    ->add($this->getMock('Imbo\Image\Transformation\Resize'))
                    ->add($this->getMock('Imbo\Image\Transformation\Thumbnail'));

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
