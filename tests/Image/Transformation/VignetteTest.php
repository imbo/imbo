<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imagick;
use Imbo\Model\Image;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Vignette
 */
class VignetteTest extends TransformationTests
{
    protected function getTransformation(): Vignette
    {
        return new Vignette();
    }

    /**
     * @covers ::transform
     */
    public function testCanVignetteImages(): void
    {
        $image = $this->createConfiguredMock(Image::class, [
            'getWidth' => 640,
            'getHeight' => 480,
        ]);

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
