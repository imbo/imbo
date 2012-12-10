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

namespace Imbo\UnitTest\Image\Transformation;

use Imbo\Image\Transformation\Transformation,
    Imbo\Image\Image,
    Imagick,
    ReflectionMethod;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\Image\Transformation\Transformation
 */
class TransformationTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Transformation
     */
    private $transformation;

    /**
     * Set up the transformation instance
     */
    public function setUp() {
        $this->transformation = new TransformationImpl();
    }

    /**
     * Tear down the transformation instance
     */
    public function tearDown() {
        $this->transformation = null;
    }

    /**
     * @covers Imbo\Image\Transformation\Transformation::setImagick
     * @covers Imbo\Image\Transformation\Transformation::getImagick
     */
    public function testCanSetAndGetImagick() {
        $imagick = new Imagick();
        $this->assertSame($this->transformation, $this->transformation->setImagick($imagick));
        $this->assertSame($imagick, $this->transformation->getImagick());
    }

    /**
     * @covers Imbo\Image\Transformation\Transformation::getImagick
     */
    public function testCanCreateAnImagickInstanceItself() {
        $this->assertInstanceOf('Imagick', $this->transformation->getImagick());
    }

    /**
     * Get different colors and their formatted version
     *
     * @return array[]
     */
    public function getColors() {
        return array(
            array('red', 'red'),
            array('000', '#000'),
            array('000000', '#000000'),
            array('fff', '#fff'),
            array('FFF', '#FFF'),
            array('FFF000', '#FFF000'),
            array('#FFF', '#FFF'),
            array('#FFF000', '#FFF000'),
        );
    }

    /**
     * @dataProvider getColors
     * @covers Imbo\Image\Transformation\Transformation::formatColor
     */
    public function testCanFormatColors($color, $expected) {
        $method = new ReflectionMethod('Imbo\Image\Transformation\Transformation', 'formatColor');
        $method->setAccessible(true);

        $this->assertSame($expected, $method->invoke($this->transformation, $color));
    }
}

class TransformationImpl extends Transformation {
    public function applyToImage(Image $image) {}
}
