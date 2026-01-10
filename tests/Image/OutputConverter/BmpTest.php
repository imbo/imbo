<?php declare(strict_types=1);

namespace Imbo\Image\OutputConverter;

use Imagick;
use ImagickException;
use Imbo\Exception\OutputConverterException;
use Imbo\Http\Response\Response;
use Imbo\Model\Image;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Bmp::class)]
class BmpTest extends TestCase
{
    private Bmp $converter;

    protected function setUp(): void
    {
        $this->converter = new Bmp();
    }

    public function testReturnsSupportedMimeTypes(): void
    {
        $types = $this->converter->getSupportedMimeTypes();
        $this->assertContains('image/bmp', array_keys($types));
    }

    public function testCanConvertImage(): void
    {
        $extension = 'bmp';
        $mimeType = 'image/bmp';

        $imagick = $this->createMock(Imagick::class);
        $imagick
            ->expects($this->once())
            ->method('setImageFormat')
            ->with($extension);

        $image = $this->createMock(Image::class);
        $image
            ->expects($this->once())
            ->method('setHasBeenTransformed')
            ->with(true);

        $this->assertNull($this->converter->convert($imagick, $image, $extension, $mimeType));
    }

    public function testThrowsExceptionOnImagickFailure(): void
    {
        $extension = 'bmp';

        $imagick = $this->createMock(Imagick::class);
        $imagick
            ->expects($this->once())
            ->method('setImageFormat')
            ->with($extension)
            ->willThrowException(new ImagickException('some error'));

        $this->expectExceptionObject(new OutputConverterException('some error', Response::HTTP_BAD_REQUEST));
        $this->converter->convert(
            $imagick,
            $this->createStub(Image::class),
            $extension,
            'image/bmp',
        );
    }
}
