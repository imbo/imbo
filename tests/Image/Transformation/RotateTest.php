<?php declare(strict_types=1);

namespace Imbo\Image\Transformation;

use Imagick;
use Imbo\Model\Image;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Rotate::class)]
class RotateTest extends TransformationTests
{
    protected function getTransformation(): Rotate
    {
        return new Rotate();
    }

    #[DataProvider('getRotateParams')]
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
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR.'/image.png'));

        $this->getTransformation()
            ->setImage($image)
            ->setImagick($imagick)
            ->transform([
                'angle' => $angle,
                'bg' => 'fff',
            ]);
    }

    /**
     * @return array<string,array{angle:int,width:int,height:int}>
     */
    public static function getRotateParams(): array
    {
        return [
            '90 angle' => [
                'angle' => 90,
                'width' => 463,
                'height' => 665,
            ],
            '180 angle' => [
                'angle' => 180,
                'width' => 665,
                'height' => 463,
            ],
        ];
    }
}
