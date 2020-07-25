<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imbo\Exception\TransformationException;
use Imbo\Model\Image;
use Imagick;
use ImagickException;

/**
 * @coversDefaultClass Imbo\Image\Transformation\Strip
 */
class StripTest extends TransformationTests {
    protected function getTransformation() : Strip {
        return new Strip();
    }

    /**
     * @covers ::transform
     */
    public function testStripMetadata() : void {
        $image = $this->createMock(Image::class);
        $image
            ->expects($this->once())
            ->method('setHasBeenTransformed')
            ->with(true)
            ->willReturn($image);

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR . '/exif-logo.jpg'));

        $exifExists = false;

        foreach (array_keys($imagick->getImageProperties()) as $key) {
            if (substr($key, 0, 5) === 'exif:') {
                $exifExists = true;
                break;
            }
        }

        if (!$exifExists) {
            $this->fail('Image is missing EXIF data');
        }

        $this->getTransformation()
            ->setImage($image)
            ->setImagick($imagick)
            ->transform([]);

        foreach (array_keys($imagick->getImageProperties()) as $key) {
            $this->assertStringStartsNotWith('exif', $key);
        }
    }

    /**
     * @covers ::transform
     */
    public function testThrowsCorrectExceptionWhenAnErrorOccurs() : void {
        $imagick = $this->createMock(Imagick::class);
        $imagick
            ->expects($this->once())
            ->method('stripImage')
            ->willThrowException($e = new ImagickException('error'));

        $this->expectExceptionObject(new TransformationException('error', 400, $e));
        (new Strip())
            ->setImagick($imagick)
            ->transform([]);
    }

    /**
     * @covers ::transform
     */
    public function testReloadsImage() : void {
        $image = $this->createMock(Image::class);
        $image
            ->expects($this->once())
            ->method('setHasBeenTransformed')
            ->with(true);

        $imagick = $this->createConfiguredMock(Imagick::class, [
            'getImageBlob' => 'foo',
        ]);
        $imagick
            ->expects($this->once())
            ->method('clear');
        $imagick
            ->expects($this->once())
            ->method('readImageBlob')
            ->with('foo');

        (new Strip())
            ->setImagick($imagick)
            ->setImage($image)
            ->transform([]);
    }
}
