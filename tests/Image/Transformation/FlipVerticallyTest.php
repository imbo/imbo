<?php declare(strict_types=1);

namespace Imbo\Image\Transformation;

use Imagick;
use Imbo\Model\Image;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FlipVertically::class)]
class FlipVerticallyTest extends TransformationTests
{
    protected function getTransformation(): FlipVertically
    {
        return new FlipVertically();
    }

    public function testCanFlipTheImage(): void
    {
        $image = $this->createMock(Image::class);
        $image
            ->expects($this->once())
            ->method('setHasBeenTransformed')
            ->with(true)
            ->willReturn($image);

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR.'/image.png'));

        $this->getTransformation()
            ->setImage($image)
            ->setImagick($imagick)
            ->transform([]);
    }
}
