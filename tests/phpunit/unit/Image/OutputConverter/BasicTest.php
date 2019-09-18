<?php declare(strict_types=1);
namespace ImboUnitTest\Image\OutputConverter;

use Imbo\Image\OutputConverter\Basic;
use Imbo\Exception\OutputConverterException;
use PHPUnit\Framework\TestCase;
use ImagickException;

/**
 * @coversDefaultClass Imbo\Image\OutputConverter\Basic
 */
class BasicTest extends TestCase {
    /**
     * @var Basic
     */
    private $converter;

    /**
     * Set up the loader
     */
    public function setUp() : void {
        $this->converter = new Basic();
    }

    /**
     * @covers ::getSupportedMimeTypes
     */
    public function testReturnsSupportedMimeTypes() : void {
        $types = $this->converter->getSupportedMimeTypes();

        $this->assertIsArray($types);

        $this->assertContains('image/png', array_keys($types));
        $this->assertContains('image/jpeg', array_keys($types));
        $this->assertContains('image/gif', array_keys($types));
    }

    /**
     * @covers ::convert
     */
    public function testCanConvertImage() : void {
        $extension = 'png';
        $mimeType = 'image/png';

        $imagick = $this->createMock('Imagick');
        $imagick->expects($this->once())
                ->method('setImageFormat')
                ->with($extension);

        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())
              ->method('hasBeenTransformed')
              ->with(true);

        $this->assertNull($this->converter->convert($imagick, $image, $extension, $mimeType));
    }

    /**
     * @covers ::convert
     */
    public function testThrowsExceptionOnImagickFailure() : void {
        $extension = 'png';

        $imagick = $this->createMock('Imagick');
        $imagick->expects($this->once())
                ->method('setImageFormat')
                ->with($extension)
                ->will($this->throwException(new ImagickException('some error')));

        $this->expectExceptionObject(new OutputConverterException('some error', 400));
        $this->converter->convert(
            $imagick,
            $this->createMock('Imbo\Model\Image'),
            $extension,
            'image/png'
        );
    }
}
