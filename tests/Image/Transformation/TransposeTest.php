<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imagick;
use Imbo\Model\Image;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Transpose
 */
class TransposeTest extends TransformationTests
{
    protected function getTransformation(): Transpose
    {
        return new Transpose();
    }

    /**
     * @covers ::transform
     */
    public function testCanTransformImage(): void
    {
        $image = $this->createMock(Image::class);
        $image
            ->expects($this->once())
            ->method('setHasBeenTransformed')
            ->with(true)
            ->willReturn($image);

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $this->getTransformation()
            ->setImage($image)
            ->setImagick($imagick)
            ->transform([]);
    }
}
