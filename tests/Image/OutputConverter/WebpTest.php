<?php declare(strict_types=1);
namespace Imbo\Image\OutputConverter;

use Imagick;
use ImagickException;
use Imbo\Exception\OutputConverterException;
use Imbo\Http\Response\Response;
use Imbo\Model\Image;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Image\OutputConverter\Webp
 */
class WebpTest extends TestCase
{
    private Webp $converter;

    public function setUp(): void
    {
        $this->converter = new Webp();
    }

    /**
     * @covers ::getSupportedMimeTypes
     */
    public function testReturnsSupportedMimeTypes(): void
    {
        $types = $this->converter->getSupportedMimeTypes();
        $this->assertContains('image/webp', array_keys($types));
    }

    /**
     * @covers ::convert
     */
    public function testCanConvertImage(): void
    {
        $extension = 'webp';
        $mimeType = 'image/webp';

        /** @var Imagick&MockObject */
        $imagick = $this->createMock(Imagick::class);
        $imagick
            ->expects($this->once())
            ->method('setImageFormat')
            ->with($extension);

        /** @var Image&MockObject */
        $image = $this->createMock(Image::class);
        $image
            ->expects($this->once())
            ->method('setHasBeenTransformed')
            ->with(true);

        $this->assertNull($this->converter->convert($imagick, $image, $extension, $mimeType));
    }

    /**
     * @covers ::convert
     */
    public function testThrowsExceptionOnImagickFailure(): void
    {
        $extension = 'webp';

        /** @var Imagick&MockObject */
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
