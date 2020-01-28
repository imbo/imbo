<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imbo\Image\Transformation\Desaturate;
use Imbo\Model\Image;
use Imagick;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Desaturate
 */
class DesaturateTest extends TransformationTests {
    protected function getTransformation() : Desaturate {
        return new Desaturate();
    }

    /**
     * @covers ::transform
     */
    public function testCanDesaturateImages() : void {
        $image = $this->createMock(Image::class);
        $image
            ->expects($this->once())
            ->method('hasBeenTransformed')
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
