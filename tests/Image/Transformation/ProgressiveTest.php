<?php declare(strict_types=1);

namespace Imbo\Image\Transformation;

use Imagick;
use Imbo\Model\Image;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Progressive::class)]
class ProgressiveTest extends TransformationTests
{
    protected function getTransformation(): Progressive
    {
        return new Progressive();
    }

    public function testCanMakeTheImageProgressive(): void
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
