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

use Imbo\Image\Transformation\AutoRotate;

/**
 * @covers Imbo\Image\Transformation\AutoRotate
 * @group unit
 * @group transformations
 */
class AutoRotateTest extends \PHPUnit_Framework_TestCase {
    /**
     * @covers Imbo\Image\Transformation\AutoRotate::transform
     */
    public function testWillNotUpdateTheImageWhenNotNeeded() {
        $image = $this->getMock('Imbo\Model\Image');

        $imagick = $this->getMock('Imagick');
        $imagick->expects($this->once())->method('getImageOrientation')->will($this->returnValue(0));
        $imagick->expects($this->never())->method('setImageOrientation');

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->once())->method('getArgument')->with('image')->will($this->returnValue($image));

        $transformation = new AutoRotate();
        $transformation->setImagick($imagick);
        $transformation->transform($event);
    }
}
