<?php declare(strict_types=1);
namespace Imbo\Image\OutputConverter;

use Imbo\Image\OutputConverter\Bmp;
use Imbo\Exception\OutputConverterException;
use Imbo\Model\Image;
use PHPUnit\Framework\TestCase;
use Imagick;
use ImagickException;

/**
 * @coversDefaultClass Imbo\Image\OutputConverter\Bmp
 */
class BmpTest extends TestCase {
    private $converter;

    public function setUp() : void {
        $this->converter = new Bmp();
    }

    /**
     * @covers ::getSupportedMimeTypes
     */
    public function testReturnsSupportedMimeTypes() : void {
        $types = $this->converter->getSupportedMimeTypes();

        $this->assertIsArray($types);
        $this->assertContains('image/bmp', array_keys($types));
    }

    /**
     * @covers ::convert
     */
    public function testCanConvertImage() : void {
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
            ->method('hasBeenTransformed')
            ->with(true);

        $this->assertNull($this->converter->convert($imagick, $image, $extension, $mimeType));
    }

    /**
     * @covers ::convert
     */
    public function testThrowsExceptionOnImagickFailure() : void {
        $extension = 'bmp';

        $imagick = $this->createMock(Imagick::class);
        $imagick
            ->expects($this->once())
            ->method('setImageFormat')
            ->with($extension)
            ->willThrowException(new ImagickException('some error'));

        $this->expectExceptionObject(new OutputConverterException('some error', 400));
        $this->converter->convert(
            $imagick,
            $this->createMock('Imbo\Model\Image'),
            $extension,
            'image/bmp'
        );
    }
}
