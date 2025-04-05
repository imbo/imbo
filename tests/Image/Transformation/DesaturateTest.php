<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imagick;
use Imbo\Model\Image;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Desaturate::class)]
class DesaturateTest extends TransformationTests
{
    protected function getTransformation(): Desaturate
    {
        return new Desaturate();
    }

    public function testCanDesaturateImages(): void
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
            ->setImagick($imagick)
            ->setImage($image)
            ->transform([]);
    }
}
