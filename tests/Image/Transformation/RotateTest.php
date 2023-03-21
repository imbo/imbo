<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imagick;
use Imbo\Model\Image;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Rotate
 */
class RotateTest extends TransformationTests
{
    protected function getTransformation(): Rotate
    {
        return new Rotate();
    }

    public static function getRotateParams(): array
    {
        return [
            '90 angle' => [90, 463, 665],
            '180 angle' => [180, 665, 463],
        ];
    }

    /**
     * @dataProvider getRotateParams
     * @covers ::transform
     */
    public function testCanTransformImage(int $angle, int $width, int $height): void
    {
        $image = $this->createMock(Image::class);

        $image
            ->expects($this->once())
            ->method('setWidth')
            ->with($width)
            ->willReturnSelf();

        $image
            ->expects($this->once())
            ->method('setHeight')
            ->with($height)
            ->willReturnSelf();

        $image
            ->expects($this->once())
            ->method('setHasBeenTransformed')
            ->with(true)
            ->willReturnSelf();

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $this->getTransformation()
            ->setImage($image)
            ->setImagick($imagick)
            ->transform([
                'angle' => $angle,
                'bg' => 'fff',
            ]);
    }
}
