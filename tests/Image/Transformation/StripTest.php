<?php declare(strict_types=1);

namespace Imbo\Image\Transformation;

use Imagick;
use ImagickException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;
use Imbo\Model\Image;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Strip::class)]
class StripTest extends TransformationTests
{
    protected function getTransformation(): Strip
    {
        return new Strip();
    }

    public function testStripMetadata(): void
    {
        $image = $this->createMock(Image::class);
        $image
            ->expects($this->once())
            ->method('setHasBeenTransformed')
            ->with(true)
            ->willReturn($image);

        $imagick = new Imagick();
        $imagick->readImageBlob(file_get_contents(FIXTURES_DIR.'/exif-logo.jpg'));

        $exifExists = false;

        foreach (array_keys($imagick->getImageProperties()) as $key) {
            if ('exif:' === substr((string) $key, 0, 5)) {
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
            $this->assertStringStartsNotWith('exif', (string) $key);
        }
    }

    public function testThrowsCorrectExceptionWhenAnErrorOccurs(): void
    {
        $imagick = $this->createMock(Imagick::class);
        $imagick
            ->expects($this->once())
            ->method('stripImage')
            ->willThrowException($e = new ImagickException('error'));

        $this->expectExceptionObject(new TransformationException('error', Response::HTTP_BAD_REQUEST, $e));
        (new Strip())
            ->setImagick($imagick)
            ->transform([]);
    }

    public function testReloadsImage(): void
    {
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
