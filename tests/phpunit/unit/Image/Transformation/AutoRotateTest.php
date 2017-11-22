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

use Imagick;
use Imbo\Image\Transformation\AutoRotate;
use PHPUnit\Framework\TestCase;

/**
 * @covers Imbo\Image\Transformation\AutoRotate
 * @group unit
 * @group transformations
 */
class AutoRotateTest extends TestCase {
    /**
     * @covers Imbo\Image\Transformation\AutoRotate::transform
     */
    public function testWillNotUpdateTheImageWhenNotNeeded() {
        $image = $this->createMock('Imbo\Model\Image');

        $imagick = $this->createMock('Imagick');
        $imagick->expects($this->once())->method('getImageOrientation')->will($this->returnValue(0));
        $imagick->expects($this->never())->method('setImageOrientation');

        $transformation = new AutoRotate();
        $transformation->setImagick($imagick);
        $transformation->transform([]);
    }

    /**
     * @covers Imbo\Image\Transformation\AutoRotate::transform
     */
    public function testWillRotateWhenNeeded() {
        $imagick = $this->createMock('Imagick');
        $imagick->expects($this->once())->method('getImageOrientation')->will($this->returnValue(
            Imagick::ORIENTATION_TOPRIGHT
        ));
        $imagick->expects($this->once())->method('flopImage');
        $imagick->expects($this->once())->method('setImageOrientation')->with(Imagick::ORIENTATION_TOPLEFT);

        $transformation = new AutoRotate();
        $transformation->setImagick($imagick);
        $transformation->setImage($this->createMock('Imbo\Model\Image'));
        $transformation->transform([]);
    }
}
