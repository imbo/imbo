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

use Imbo\Image\Transformation\Modulate;

/**
 * @covers Imbo\Image\Transformation\Modulate
 * @group unit
 * @group transformations
 */
class ModulateTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Modulate
     */
    private $transformation;

    /**
     * Set up the transformation instance
     */
    public function setUp() {
        $this->transformation = new Modulate();
    }

    /**
     * Tear down the transformation instance
     */
    public function tearDown() {
        $this->transformation = null;
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getModulateParams() {
        return [
            'no params' => [
                [], 100, 100, 100,
            ],
            'some params' => [
                ['b' => 10, 's' => 50], 10, 50, 100,
            ],
            'all params' => [
                ['b' => 1, 's' => 2, 'h' => 3], 1, 2, 3,
            ],
        ];
    }

    /**
     * @dataProvider getModulateParams
     */
    public function testUsesDefaultValuesWhenParametersAreNotSpecified(array $params, $brightness, $saturation, $hue) {
        $image = $this->getMock('Imbo\Model\Image');

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('params')->will($this->returnValue($params));
        $event->expects($this->at(1))->method('getArgument')->with('image')->will($this->returnValue($image));

        $imagick = $this->getMock('Imagick');
        $imagick->expects($this->once())->method('modulateImage')->with($brightness, $saturation, $hue);

        $this->transformation->setImagick($imagick)->transform($event);
    }
}
