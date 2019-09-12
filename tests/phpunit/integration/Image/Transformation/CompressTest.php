<?php
namespace ImboIntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\Compress;
use Imagick;

/**
 * @covers Imbo\Image\Transformation\Compress
 * @group integration
 * @group transformations
 */
class CompressTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new Compress();
    }

    public function testCanTransformTheImage() {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('setOutputQualityCompression')->with(50);
        $event = $this->createMock('Imbo\EventManager\Event');

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/image.png'));

        $transformation = $this->getTransformation();
        $transformation
            ->setImagick($imagick)
            ->setImage($image)
            ->setEvent($event)
            ->transform(['level' => 50]);
    }
}
