<?php
namespace ImboIntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\Desaturate;
use Imagick;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Desaturate
 */
class DesaturateTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new Desaturate();
    }

    /**
     * @covers ::transform
     */
    public function testCanDesaturateImages() {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->with(true)->will($this->returnValue($image));

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $this->getTransformation()->setImagick($imagick)->setImage($image)->transform([]);
    }
}
