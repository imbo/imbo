<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imbo\Exception\TransformationException;
use Imbo\Model\Image;
use PHPUnit\Framework\TestCase;
use Imagick;
use ImagickException;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Strip
 */
class StripTest extends TestCase {
    /**
     * @covers ::transform
     */
    public function testThrowsCorrectExceptionWhenAnErrorOccurs() : void {
        $imagick = $this->createMock(Imagick::class);
        $imagick
            ->expects($this->once())
            ->method('stripImage')
            ->willThrowException($e = new ImagickException('error'));

        $this->expectExceptionObject(new TransformationException('error', 400, $e));
        (new Strip())
            ->setImagick($imagick)
            ->transform([]);
    }

    /**
     * @covers ::transform
     */
    public function testReloadsImage() : void {
        $image = $this->createMock(Image::class);
        $image
            ->expects($this->once())
            ->method('hasBeenTransformed')
            ->with(true);

        $imagick = $this->createConfiguredMock(Imagick::class, [
            'getImageBlob' => 'foo',
        ]);
        $imagick
            ->expects($this->once())
            ->method('clear');
        $imagick
            ->expects($this->once())
            ->method('readImageBlob')
            ->with('foo');

        (new Strip())
            ->setImagick($imagick)
            ->setImage($image)
            ->transform([]);
    }
}
