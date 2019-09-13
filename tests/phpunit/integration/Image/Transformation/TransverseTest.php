<?php
namespace ImboIntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\Transverse;
use Imagick;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Transverse
 */
class TransverseTest extends TransformationTests {
    protected function getTransformation() : Transverse {
        return new Transverse();
    }

    /**
     * @covers ::transform
     */
    public function testCanTransformImage() : void {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->with(true)->will($this->returnValue($image));

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $this->getTransformation()->setImage($image)->setImagick($imagick)->transform([]);
    }
}
