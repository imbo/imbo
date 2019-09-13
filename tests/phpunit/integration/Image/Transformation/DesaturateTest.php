<?php
namespace ImboIntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\Desaturate;
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
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->with(true)->will($this->returnValue($image));

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $this->getTransformation()->setImagick($imagick)->setImage($image)->transform([]);
    }
}
