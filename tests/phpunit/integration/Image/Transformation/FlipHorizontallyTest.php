<?php
namespace ImboIntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\FlipHorizontally;
use Imagick;

/**
 * @covers Imbo\Image\Transformation\FlipHorizontally
 * @group integration
 * @group transformations
 */
class FlipHorizontallyTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new FlipHorizontally();
    }

    /**
     * @covers Imbo\Image\Transformation\FlipHorizontally::transform
     */
    public function testCanFlipTheImage() {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->with(true)->will($this->returnValue($image));

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $this->getTransformation()->setImage($image)->setImagick($imagick)->transform([]);
    }
}
