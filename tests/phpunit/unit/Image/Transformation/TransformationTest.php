<?php
namespace ImboUnitTest\Image\Transformation;

use Imbo\Image\Transformation\Transformation;
use Imbo\Model\Image;
use Imagick;
use ReflectionMethod;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Transformation
 */
class TransformationTest extends TestCase {
    /**
     * @var Transformation
     */
    private $transformation;

    /**
     * Set up the transformation instance
     */
    public function setUp() : void {
        $this->transformation = new TransformationImpl();
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
    public function testCanFormatColors($color, $expected) : void {
        $method = new ReflectionMethod('Imbo\Image\Transformation\Transformation', 'formatColor');
        $method->setAccessible(true);

        $this->assertSame($expected, $method->invoke($this->transformation, $color));
    }
}

class TransformationImpl extends Transformation {
    public function transform(array $params = []) {}
}
