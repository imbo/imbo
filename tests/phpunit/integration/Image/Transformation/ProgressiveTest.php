<?php
namespace ImboIntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\Progressive;
use Imagick;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Progressive
 */
class ProgressiveTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new Progressive();
    }

    /**
     * @covers ::transform
     */
    public function testCanMakeTheImageProgressive() {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->with(true)->will($this->returnValue($image));

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $this->getTransformation()->setImage($image)->setImagick($imagick)->transform([]);
    }
}
