<?php declare(strict_types=1);
namespace ImboIntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\FlipVertically;
use Imagick;

/**
 * @coversDefaultClass Imbo\Image\Transformation\FlipVertically
 */
class FlipVerticallyTest extends TransformationTests {
    protected function getTransformation() : FlipVertically {
        return new FlipVertically();
    }

    /**
     * @covers ::transform
     */
    public function testCanFlipTheImage() : void {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->with(true)->will($this->returnValue($image));

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $this->getTransformation()->setImage($image)->setImagick($imagick)->transform([]);
    }
}
