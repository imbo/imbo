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
        return array(
            'no params' => array(
                array(), 0, 1,
            ),
            'positive contrast' => array(
                array('sharpen' => 2), 1, 2,
            ),
            'zero contrast' => array(
                array('sharpen' => 0), 0, 1,
            ),
            'negative contrast' => array(
                array('sharpen' => -2), 0, 3,
            ),
        );
    }

    /**
     * @dataProvider getContrastParams
     */
    public function testSetsTheCorrectContrast(array $params, $contrastValue, $times) {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->with(true);
        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->at(0))->method('getArgument')->with('params')->will($this->returnValue($params));
        $event->expects($this->at(1))->method('getArgument')->with('image')->will($this->returnValue($image));

        $imagick = $this->getMock('Imagick');
        $imagick->expects($this->exactly($times))->method('contrastImage')->with($contrastValue);

        $this->transformation->setImagick($imagick);
        $this->transformation->transform($event);
    }
}
