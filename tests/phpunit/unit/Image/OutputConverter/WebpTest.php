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

use Imbo\Image\OutputConverter\Webp;
use Imbo\Exception\OutputConverterException;
use PHPUnit\Framework\TestCase;
use ImagickException;

/**
 * @coversDefaultClass Imbo\Image\OutputConverter\Webp
 */
class WebpTest extends TestCase {
    /**
     * @var Webp
     */
    private $converter;

    /**
     * Set up the loader
     */
    public function setup() {
        $this->converter = new Webp();
    }

    /**
     * @covers ::getSupportedMimeTypes
     */
    public function testReturnsSupportedMimeTypes() {
        $types = $this->converter->getSupportedMimeTypes();

        $this->assertInternalType('array', $types);

        $this->assertContains('image/webp', array_keys($types));
    }

    /**
     * @covers ::convert
     */
    public function testCanConvertImage() {
        $extension = 'webp';
        $mimeType = 'image/webp';

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
    public function testThrowsExceptionOnImagickFailure() {
        $extension = 'webp';

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
            'image/webp'
        );
    }
}
