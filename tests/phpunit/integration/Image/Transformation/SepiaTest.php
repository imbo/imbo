<?php
namespace ImboIntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\Sepia;
use Imagick;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Sepia
 */
class SepiaTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new Sepia();
    }

    /**
     * @covers ::transform
     */
    public function testCanTransformImageWithoutParams() {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->with(true);

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $this->getTransformation()->setImage($image)->setImagick($imagick)->transform([]);
    }

    /**
     * @covers ::transform
     */
    public function testCanTransformImageWithParams() {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->with(true);

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $this->getTransformation()->setImage($image)->setImagick($imagick)->transform(['threshold' => 10]);
    }
}
