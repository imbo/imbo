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

use Imbo\Image\InputLoader\Basic;
use Imbo\Image\InputLoader\Text;
use Imbo\Image\InputLoader\InputLoaderInterface;
use Imbo\Image\InputLoaderManager;
use PHPUnit_Framework_TestCase;
use stdClass;

/**
 * @coversDefaultClass Imbo\Image\InputLoaderManager
 */
class InputLoaderManagerTest extends PHPUnit_Framework_TestCase {
    /**
     * @var InputLoaderManager
     */
    private $manager;

    /**
     * Set up the manager
     */
    public function setup() {
        $this->manager = new InputLoaderManager();
    }

    /**
     * @covers ::setImagick
     */
    public function testCanSetImagickInstance() {
        $this->assertSame($this->manager, $this->manager->setImagick($this->createMock('Imagick')));
    }

    /**
     * @covers ::addLoaders
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage Given loader (stdClass) does not implement LoaderInterface
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenRegisteringWrongLoader() {
        $this->manager->addLoaders([new stdClass()]);
    }

    /**
     * @covers ::addLoaders
     */
    public function testCanAddLoadersAsStrings() {
        $this->assertSame($this->manager, $this->manager->addLoaders([
            new Basic(),
            Text::class
        ]));
    }

    /**
     * @covers ::registerLoader
     * @covers ::getExtensionFromMimeType
     */
    public function testCanGetExtensionFromMimeType() {
        $this->manager->addLoaders([
            new Basic(),
            new Text(),
        ]);
        $this->assertSame('jpg', $this->manager->getExtensionFromMimeType('image/jpeg'));
        $this->assertSame('png', $this->manager->getExtensionFromMimeType('image/png'));
        $this->assertSame('gif', $this->manager->getExtensionFromMimeType('image/gif'));
        $this->assertSame('tif', $this->manager->getExtensionFromMimeType('image/tiff'));
        $this->assertSame('txt', $this->manager->getExtensionFromMimeType('text/plain'));
    }

    /**
     * @covers ::registerLoader
     * @covers ::load
     */
    public function testCanRegisterAndUseLoaders() {
        $imagick = $this->createMock('Imagick');
        $mime = 'image/png';
        $blob = 'some data';

        $loader1 = $this->createMock(InputLoaderInterface::class);
        $loader1->expects($this->once())
                ->method('getSupportedMimeTypes')
                ->will($this->returnValue([$mime => 'png']));
        $loader1->expects($this->once())
                ->method('load')
                ->with($imagick, $blob, $mime)
                ->will($this->returnValue(false));

        $loader2 = $this->createMock(InputLoaderInterface::class);
        $loader2->expects($this->once())
                ->method('getSupportedMimeTypes')
                ->will($this->returnValue([$mime => 'png']));
        $loader2->expects($this->once())
                ->method('load')
                ->with($imagick, $blob, $mime)
                ->will($this->returnValue(null));

        $this->manager->setImagick($imagick)
                      ->registerLoader($loader1)
                      ->registerLoader($loader2);

        $this->assertSame(
            $imagick,
            $this->manager->load($mime, $blob)
        );
    }

    /**
     * @covers ::load
     */
    public function testManagerReturnsFalseWhenNoLoaderManagesToLoadTheImage() {
        $loader = $this->createConfiguredMock(InputLoaderInterface::class, [
            'getSupportedMimeTypes' => ['image/png' => 'png'],
            'load' => false,
        ]);

        $this->assertFalse(
            $this->manager->setImagick($this->createMock('Imagick'))
                          ->registerLoader($loader)
                          ->load('image/png', 'some data')
        );
    }

    /**
     * @covers ::load
     */
    public function testManagerReturnsNullWhenNoLoadersExist() {
        $this->assertNull($this->manager->load('image/png', 'some data'));
    }
}
