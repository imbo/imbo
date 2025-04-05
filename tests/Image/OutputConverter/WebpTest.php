<?php declare(strict_types=1);
namespace Imbo\Image\OutputConverter;

use Imagick;
use ImagickException;
use Imbo\Exception\OutputConverterException;
use Imbo\Http\Response\Response;
use Imbo\Model\Image;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Webp::class)]
class WebpTest extends TestCase
{
    private Webp $converter;

    public function setUp(): void
    {
        $this->converter = new Webp();
    }

    public function testReturnsSupportedMimeTypes(): void
    {
        $types = $this->converter->getSupportedMimeTypes();
        $this->assertContains('image/webp', array_keys($types));
    }

    public function testCanConvertImage(): void
    {
        $extension = 'webp';
        $mimeType = 'image/webp';

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
        $extension = 'webp';

        $imagick = $this->createMock(Imagick::class);
        $imagick
            ->expects($this->once())
            ->method('setImageFormat')
            ->with($extension)
            ->willThrowException(new ImagickException('some error'));

        $this->expectExceptionObject(new OutputConverterException('some error', Response::HTTP_BAD_REQUEST));
        $this->converter->convert(
            $imagick,
            $this->createMock(Image::class),
            $extension,
            'image/webp',
        );
    }
}
