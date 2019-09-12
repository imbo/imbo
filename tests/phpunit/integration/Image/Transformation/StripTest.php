<?php
namespace ImboIntegrationTest\Image\Transformation;

use Imbo\Image\Transformation\Strip;
use Imagick;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Strip
 */
class StripTest extends TransformationTests {
    /**
     * {@inheritdoc}
     */
    protected function getTransformation() {
        return new Strip();
    }

    /**
     * @covers ::transform
     */
    public function testStripMetadata() {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('hasBeenTransformed')->with(true)->will($this->returnValue($image));

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/exif-logo.jpg'));

        $exifExists = false;

        foreach ($imagick->getImageProperties() as $key => $value) {
            if (substr($key, 0, 5) === 'exif:') {
                $exifExists = true;
                break;
            }
        }

        if (!$exifExists) {
            $this->fail('Image is missing EXIF data');
        }

        $this->getTransformation()->setImage($image)->setImagick($imagick)->transform([]);

        foreach ($imagick->getImageProperties() as $key => $value) {
            $this->assertStringStartsNotWith('exif', $key);
        }
    }
}
