<?php
namespace ImboUnitTest\Image\Transformation;

use Imagick;
use Imbo\Image\Transformation\AutoRotate;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Image\Transformation\AutoRotate
 */
class AutoRotateTest extends TestCase {
    /**
     * @covers Imbo\Image\Transformation\AutoRotate::transform
     */
    public function testWillNotUpdateTheImageWhenNotNeeded() : void {
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
    public function testWillRotateWhenNeeded() : void {
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
