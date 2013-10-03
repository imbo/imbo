<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\Image\Transformation;

use Imbo\Image\Transformation\Transformation,
    Imbo\Model\Image,
    Imagick,
    ReflectionMethod;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
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
        $this->assertEquals($imagick, $this->transformation->getImagick());
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

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 */
class TransformationImpl extends Transformation {
    public function applyToImage(Image $image, array $params = array()) {}
}
