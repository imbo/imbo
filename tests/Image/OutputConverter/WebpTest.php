<?php declare(strict_types=1);
namespace Imbo\Image\OutputConverter;

use Imbo\Image\OutputConverter\Webp;
use Imbo\Exception\OutputConverterException;
use PHPUnit\Framework\TestCase;
use Imagick;
use ImagickException;
use Imbo\Model\Image;

/**
 * @coversDefaultClass Imbo\Image\OutputConverter\Webp
 */
class WebpTest extends TestCase {
    private $converter;

    public function setUp() : void {
        $this->converter = new Webp();
    }

    /**
     * @covers ::getSupportedMimeTypes
     */
    public function testReturnsSupportedMimeTypes() : void {
        $types = $this->converter->getSupportedMimeTypes();

        $this->assertIsArray($types);
        $this->assertContains('image/webp', array_keys($types));
    }

    /**
     * @covers ::convert
     */
    public function testCanConvertImage() : void {
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
            ->method('hasBeenTransformed')
            ->with(true);

        $this->assertNull($this->converter->convert($imagick, $image, $extension, $mimeType));
    }

    /**
     * @covers ::convert
     */
    public function testThrowsExceptionOnImagickFailure() : void {
        $extension = 'webp';

        $imagick = $this->createMock(Imagick::class);
        $imagick
            ->expects($this->once())
            ->method('setImageFormat')
            ->with($extension)
            ->willThrowException(new ImagickException('some error'));

        $this->expectExceptionObject(new OutputConverterException('some error', 400));
        $this->converter->convert(
            $imagick,
            $this->createMock(Image::class),
            $extension,
            'image/webp'
        );
    }
}
