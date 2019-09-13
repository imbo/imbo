<?php
namespace ImboUnitTest\Image\Transformation;

use Imbo\Image\Transformation\Strip;
use Imbo\Exception\TransformationException;
use ImagickException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Strip
 */
class StripTest extends TestCase {
    public function testThrowsCorrectExceptionWhenAnErrorOccurs() : void {
        $imagickException = new ImagickException('error');

        $imagick = $this->createMock('Imagick');
        $imagick->expects($this->once())->method('stripImage')->will($this->throwException($imagickException));

        $transformation = new Strip();
        $this->expectExceptionObject(new TransformationException('error', 400));
        $transformation->setImagick($imagick)->transform([]);
    }

    public function testReloadsImageIfNewerImagick() : void {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->with(true);

        $imagick = $this->createMock('Imagick');
        $imagick->expects($this->once())->method('getImageBlob')->will($this->returnValue('foo'));
        $imagick->expects($this->once())->method('clear');
        $imagick->expects($this->once())->method('readImageBlob')->with('foo');

        $transformation = new Strip();
        $transformation->setImagick($imagick)->setImage($image)->transform([]);
    }
}
