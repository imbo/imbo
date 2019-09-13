<?php
namespace ImboIntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\FlipHorizontally;
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
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->with(true)->will($this->returnValue($image));

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $this->getTransformation()->setImage($image)->setImagick($imagick)->transform([]);
    }
}
