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

use Imbo\Image\Transformation\Contrast;

/**
 * @covers Imbo\Image\Transformation\Contrast
 * @group unit
 * @group transformations
 */
class ContrastTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Contrast
     */
    private $transformation;

    /**
     * Set up the transformation instance
     */
    public function setUp() {
        $this->transformation = new Contrast();
    }

    /**
     * Tear down the transformation instance
     */
    public function tearDown() {
        $this->transformation = null;
    }

    public function getContrastParams() {
        $quantumRange = 65535;

        return [
            'no params' => [
                [], true, true, 1, $quantumRange * 0.5
            ],
            'positive contrast' => [
                ['alpha' => 2.5], true, true, 2.5, $quantumRange * 0.5
            ],
            'zero contrast' => [
                ['alpha' => 0], false, false, false, false,
            ],
            'negative contrast, specific beta' => [
                ['alpha' => -2, 'beta' => 0.75], true, false, 2.0, $quantumRange * 0.75
            ],
        ];
    }

    /**
     * @dataProvider getContrastParams
     */
    public function testSetsTheCorrectContrast(array $params, $shouldTransform, $sharpen, $alpha, $beta) {
        $image = $this->getMock('Imbo\Model\Image');
        $event = $this->getMock('Imbo\EventManager\Event');
        $imagick = $this->getMock('Imagick');

        $event->expects($this->at(0))->method('getArgument')->with('params')->will($this->returnValue($params));

        if ($shouldTransform) {
            $event->expects($this->at(1))->method('getArgument')->with('image')->will($this->returnValue($image));
            $image->expects($this->once())->method('hasBeenTransformed')->with(true);
        } else {
            $image->expects($this->never())->method('hasBeenTransformed');
        }

        $imagick->expects($this->any())->method('getQuantumRange')->will($this->returnValue(['quantumRangeLong' => 65535]));

        $howMany = $shouldTransform ? $this->once() : $this->never();
        $imagick->expects($howMany)->method('sigmoidalContrastImage')->with($sharpen, $alpha, $beta);

        $this->transformation->setImagick($imagick);
        $this->transformation->transform($event);
    }
}
