<?php declare(strict_types=1);
namespace ImboIntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\FlipHorizontally;
use Imbo\Model\Image;
use Imagick;

/**
 * @coversDefaultClass Imbo\Image\Transformation\FlipHorizontally
 */
class FlipHorizontallyTest extends TransformationTests {
    protected function getTransformation() : FlipHorizontally {
        return new FlipHorizontally();
    }

    /**
     * @covers ::transform
     */
    public function testCanFlipTheImage() : void {
        $image = $this->createMock(Image::class);
        $image->expects($this->once())->method('hasBeenTransformed')->with(true)->will($this->returnValue($image));

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $this->getTransformation()->setImage($image)->setImagick($imagick)->transform([]);
    }
}
