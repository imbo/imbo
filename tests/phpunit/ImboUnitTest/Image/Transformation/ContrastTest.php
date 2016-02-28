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
        $imagick = new \Imagick();
        if (is_callable([$imagick, 'getQuantumRange'])) {
            $quantumRange = $imagick->getQuantumRange();
        } else {
            $quantumRange = \Imagick::getQuantumRange();
        }

        $quantumRange = $quantumRange['quantumRangeLong'];

        return [
            'no params' => [
                [], true
            ],
            'positive contrast' => [
                ['alpha' => 2.5], true
            ],
            'zero contrast' => [
                ['alpha' => 0], false
            ],
            'negative contrast, specific beta' => [
                ['alpha' => -2, 'beta' => 0.75], true
            ],
        ];
    }

    /**
     * @dataProvider getContrastParams
     */
    public function testSetsTheCorrectContrast(array $params, $shouldTransform) {
        $image = $this->getMock('Imbo\Model\Image');
        $event = $this->getMock('Imbo\EventManager\Event');

        $imagick = new \Imagick();
        $imagick->newImage(16, 16, '#fff');

        $event->expects($this->at(0))->method('getArgument')->with('params')->will($this->returnValue($params));

        if ($shouldTransform) {
            $event->expects($this->at(1))->method('getArgument')->with('image')->will($this->returnValue($image));
            $image->expects($this->once())->method('hasBeenTransformed')->with(true);
        } else {
            $image->expects($this->never())->method('hasBeenTransformed');
        }

        $howMany = $shouldTransform ? $this->once() : $this->never();

        $this->transformation->setImagick($imagick);
        $this->transformation->transform($event);
    }
}
