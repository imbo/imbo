<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Image;

use Imbo\Model\Image;
use Imbo\Image\OutputConverterManager;
use Imbo\Image\OutputConverter\Basic;
use Imbo\Image\OutputConverter\Bmp;
use Imbo\Image\OutputConverter\Webp;
use Imbo\Image\OutputConverter\OutputConverterInterface;
use PHPUnit\Framework\TestCase;
use Imagick;
use stdClass;

/**
 * @coversDefaultClass Imbo\Image\OutputConverterManager
 */
class OutputConverterManagerTest extends TestCase {
    /**
     * @var OutputConverterManager
     */
    private $manager;

    /**
     * Set up the manager
     */
    public function setup() {
        $this->manager = new OutputConverterManager();
    }

    /**
     * @covers ::setImagick
     */
    public function testCanSetImagickInstance() {
        $this->assertSame($this->manager, $this->manager->setImagick($this->createMock('Imagick')));
    }

    /**
     * @covers ::addConverters
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage Given converter (stdClass) does not implement OutputConverterInterface
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenRegisteringWrongConverter() {
        $this->manager->addConverters([new stdClass()]);
    }


    /**
     * @covers ::addConverters
     */
    public function testCanAddConvertersAsStrings() {
        $this->assertSame($this->manager, $this->manager->addConverters([
            new Basic(),
            new Bmp(),
            Webp::class
        ]));
    }

    /**
     * @covers ::getSupportedExtensions
     * @covers ::getSupportedMimeTypes
     * @covers ::getMimeTypeFromExtension
     * @covers ::getExtensionFromMimeType
     * @covers ::getMimeTypeToExtensionMap
     * @covers ::getExtensionToMimeTypeMap
     * @covers ::supportsExtension
     * @covers ::registerConverter
     */
    public function testCanRegisterConverters() {
        // Assert that everything is empty from the start
        $this->assertEmpty(
            $this->manager->getSupportedExtensions(),
            'Expected no supported extensions'
        );
        $this->assertEmpty(
            $this->manager->getSupportedMimeTypes(),
            'Expected no supported mime types'
        );
        $this->assertNull(
            $this->manager->getMimeTypeFromExtension('png'),
            'Did not expect to get mime type from extension'
        );
        $this->assertNull(
            $this->manager->getExtensionFromMimeType('image/png'),
            'Did not expect to get extension from mime type'
        );
        $this->assertEmpty(
            $this->manager->getMimeTypeToExtensionMap(),
            'Expected empty mime type to extension map'
        );
        $this->assertEmpty(
            $this->manager->getExtensionToMimeTypeMap(),
            'Expected empty extension to mime type map'
        );
        $this->assertFalse(
            $this->manager->supportsExtension('png'),
            'Did not expect to support given extension'
        );

        $converter1 = $this->createConfiguredMock(OutputConverterInterface::class, [
            'getSupportedMimeTypes' => [
                'image/png' => 'png',
                'image/jpeg' => ['jpg', 'jpeg'],
            ],
        ]);
        $converter2 = $this->createConfiguredMock(OutputConverterInterface::class, [
            'getSupportedMimeTypes' => [
                'image/gif' => 'gif',
                'image/png' => ['png'],
            ],
        ]);

        $this->manager->registerConverter($converter1)
                      ->registerConverter($converter2);

        $this->assertCount(
            4,
            $supportedExtensions = $this->manager->getSupportedExtensions(),
            sprintf('Expected to 4 supported extensions, got %d', count($supportedExtensions))
        );

        foreach (['jpg', 'jpeg', 'gif', 'png'] as $ext) {
            $this->assertContains($ext, $supportedExtensions);
        }

        $this->assertCount(
            3,
            $supportedMimeTypes = $this->manager->getSupportedMimeTypes(),
            sprintf('Expected to 3 supported mime types, got %d', count($supportedMimeTypes))
        );

        foreach (['image/jpeg', 'image/gif', 'image/png'] as $mime) {
            $this->assertContains($mime, $supportedMimeTypes);
        }

        $this->assertSame('image/png', $this->manager->getMimeTypeFromExtension('png'));
        $this->assertSame('image/gif', $this->manager->getMimeTypeFromExtension('gif'));
        $this->assertSame('image/jpeg', $this->manager->getMimeTypeFromExtension('jpg'));
        $this->assertSame('image/jpeg', $this->manager->getMimeTypeFromExtension('jpeg'));

        $this->assertSame('png', $this->manager->getExtensionFromMimeType('image/png'));
        $this->assertSame('gif', $this->manager->getExtensionFromMimeType('image/gif'));
        $this->assertSame('jpg', $this->manager->getExtensionFromMimeType('image/jpeg'));

        $this->assertSame([
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/gif' => 'gif',
        ], $this->manager->getMimeTypeToExtensionMap());

        $this->assertSame([
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
        ], $this->manager->getExtensionToMimeTypeMap());

        foreach (['jpg', 'jpeg', 'gif', 'png'] as $ext) {
            $this->assertTrue(
                $this->manager->supportsExtension($ext),
                sprintf('Expected to support "%s"', $ext)
            );
        }
    }

    /**
     * @covers ::convert
     * @covers ::supportsExtension
     * @covers ::getMimeTypeFromExtension
     */
    public function testCanConvertImages() {
        $mime = 'image/png';
        $extension = 'png';

        $imagick = $this->createMock(Imagick::class);

        $image = $this->createMock(Image::class);
        $image->expects($this->once())
              ->method('setMimeType')
              ->with($mime);

        $converter = $this->createConfiguredMock(OutputConverterInterface::class, [
            'getSupportedMimeTypes' => [
                $mime => $extension,
            ],
        ]);
        $converter->expects($this->once())
                  ->method('convert')
                  ->with($imagick, $image, $extension, $mime)
                  ->willReturn(true);

        $this->assertTrue(
            $this->manager->setImagick($imagick)
                          ->registerConverter($converter)
                          ->convert($image, $extension, $mime),
            'Exected convert method to return true'
        );
    }

    /**
     * @covers ::convert
     * @covers ::supportsExtension
     */
    public function testCanConvertImageUsingMimeType() {
        $mime = 'image/jpeg';
        $extension = 'jpg';

        $imagick = $this->createMock(Imagick::class);

        $image = $this->createMock(Image::class);
        $image->expects($this->once())
              ->method('setMimeType')
              ->with($mime);

        $converter = $this->createConfiguredMock(OutputConverterInterface::class, [
            'getSupportedMimeTypes' => [
                $mime => $extension,
            ],
        ]);
        $converter->expects($this->once())
                  ->method('convert')
                  ->with($imagick, $image, 'jpeg', $mime)
                  ->willReturn(true);

        $this->assertTrue(
            $this->manager->setImagick($imagick)
                          ->registerConverter($converter)
                          ->convert($image, 'jpeg', $mime),
            'Exected convert method to return true'
        );
    }

    /**
     * @covers ::convert
     */
    public function testReturnsNullWhenImageCantBeConverted() {
        $converter = $this->createConfiguredMock(OutputConverterInterface::class, [
            'getSupportedMimeTypes' => [
                'image/png' => 'png',
                'image/jpeg' => 'jpg',
                'image/gif' => 'gif',
            ],
            'convert' => false,
        ]);

        $this->assertNull(
            $this->manager->setImagick($this->createMock(Imagick::class))
                          ->registerConverter($converter)
                          ->convert($this->createMock(Image::class), 'png', 'image/png')
        );
    }
}
