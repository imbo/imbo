<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Image\Transformation;

use Imbo\Image\Transformation\Transformation,
    Imbo\Model\Image,
    Imagick,
    ReflectionMethod;

/**
 * @covers Imbo\Image\Transformation\Transformation
 * @group unit
 * @group transformations
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
     * Get different colors and their formatted version
     *
     * @return array[]
     */
    public function getColors() {
        return [
            ['red', 'red'],
            ['000', '#000'],
            ['000000', '#000000'],
            ['fff', '#fff'],
            ['FFF', '#FFF'],
            ['FFF000', '#FFF000'],
            ['#FFF', '#FFF'],
            ['#FFF000', '#FFF000'],
        ];
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
    public function applyToImage(Image $image, array $params = []) {}
}
