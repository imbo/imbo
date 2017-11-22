<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Image\OutputConverter;

use Imbo\Image\OutputConverter\Bmp;
use PHPUnit\Framework\TestCase;
use ImagickException;

/**
 * @coversDefaultClass Imbo\Image\OutputConverter\Bmp
 */
class BmpTest extends TestCase {
    /**
     * @var Bmp
     */
    private $converter;

    /**
     * Set up the loader
     */
    public function setup() {
        $this->converter = new Bmp();
    }

    /**
     * @covers ::getSupportedMimeTypes
     */
    public function testReturnsSupportedMimeTypes() {
        $types = $this->converter->getSupportedMimeTypes();

        $this->assertInternalType('array', $types);

        $this->assertContains('image/bmp', array_keys($types));
    }

    /**
     * @covers ::convert
     */
    public function testCanConvertImage() {
        $extension = 'bmp';
        $mimeType = 'image/bmp';

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
     * @expectedException Imbo\Exception\OutputConverterException
     * @expectedExceptionMessage some error
     * @expectedExceptionCode 400
     */
    public function testThrowsExceptionOnImagickFailure() {
        $extension = 'bmp';

        $imagick = $this->createMock('Imagick');
        $imagick->expects($this->once())
                ->method('setImageFormat')
                ->with($extension)
                ->will($this->throwException(new ImagickException('some error')));

        $this->assertNull($this->converter->convert($imagick, $this->createMock('Imbo\Model\Image'), $extension, 'image/bmp'));
    }
}
