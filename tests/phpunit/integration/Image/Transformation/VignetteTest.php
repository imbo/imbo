<?php
namespace ImboIntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\Vignette;
use Imagick;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Vignette
 */
class VignetteTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new Vignette();
    }

    /**
     * @covers ::transform
     */
    public function testCanVignetteImages() {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->with(true)->will($this->returnValue($image));
        $image->expects($this->once())->method('getWidth')->will($this->returnValue(640));
        $image->expects($this->once())->method('getHeight')->will($this->returnValue(480));

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $this->getTransformation()->setImage($image)->setImagick($imagick)->transform([]);
    }
}
