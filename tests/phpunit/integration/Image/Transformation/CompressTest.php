<?php declare(strict_types=1);
namespace ImboIntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\Compress;
use Imagick;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Compress
 */
class CompressTest extends TransformationTests {
    protected function getTransformation() : Compress {
        return new Compress();
    }

    public function testCanTransformTheImage() : void {
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
