<?php
namespace ImboIntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\Transpose;
use Imagick;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Transpose
 */
class TransposeTest extends TransformationTests {
    protected function getTransformation() : Transpose {
        return new Transpose();
    }

    /**
     * @covers ::transform
     */
    public function testCanTransformImage() {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->with(true)->will($this->returnValue($image));

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $this->getTransformation()->setImage($image)->setImagick($imagick)->transform([]);
    }
}
