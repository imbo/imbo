<?php declare(strict_types=1);
namespace ImboIntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\Sepia;
use Imbo\Model\Image;
use Imagick;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Sepia
 */
class SepiaTest extends TransformationTests {
    protected function getTransformation() : Sepia {
        return new Sepia();
    }

    /**
     * @covers ::transform
     */
    public function testCanTransformImageWithoutParams() : void {
        $image = $this->createMock(Image::class);
        $image->expects($this->once())->method('hasBeenTransformed')->with(true);

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $this->getTransformation()->setImage($image)->setImagick($imagick)->transform([]);
    }

    /**
     * @covers ::transform
     */
    public function testCanTransformImageWithParams() : void {
        $image = $this->createMock(Image::class);
        $image->expects($this->once())->method('hasBeenTransformed')->with(true);

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $this->getTransformation()->setImage($image)->setImagick($imagick)->transform(['threshold' => 10]);
    }
}
