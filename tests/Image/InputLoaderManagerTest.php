<?php declare(strict_types=1);

namespace Imbo\Image;

use Imagick;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Http\Response\Response;
use Imbo\Image\InputLoader\Basic;
use Imbo\Image\InputLoader\InputLoaderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(InputLoaderManager::class)]
class InputLoaderManagerTest extends TestCase
{
    private InputLoaderManager $manager;

    protected function setUp(): void
    {
        $this->manager = new InputLoaderManager();
    }

    public function testCanSetImagickInstance(): void
    {
        $this->assertSame(
            $this->manager,
            $this->manager->setImagick($this->createStub(Imagick::class)),
        );
    }

    public function testThrowsExceptionWhenRegisteringWrongLoader(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException(
            'Given loader (stdClass) does not implement LoaderInterface',
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ));
        /** @psalm-suppress InvalidArgument */
        $this->manager->addLoaders([new stdClass()]);
    }

    public function testCanAddLoadersAsStrings(): void
    {
        $this->assertSame(
            $this->manager,
            $this->manager->addLoaders([
                new Basic(),
            ]),
        );
    }

    public function testCanGetExtensionFromMimeType(): void
    {
        $this->manager->addLoaders([
            new Basic(),
        ]);
        $this->assertSame('jpg', $this->manager->getExtensionFromMimeType('image/jpeg'));
        $this->assertSame('png', $this->manager->getExtensionFromMimeType('image/png'));
        $this->assertSame('gif', $this->manager->getExtensionFromMimeType('image/gif'));
        $this->assertSame('tif', $this->manager->getExtensionFromMimeType('image/tiff'));
    }

    public function testCanRegisterAndUseLoaders(): void
    {
        $imagick = $this->createStub(Imagick::class);
        $mime = 'image/png';
        $blob = 'some data';

        $loader1 = $this->createMock(InputLoaderInterface::class);
        $loader1
            ->expects($this->once())
            ->method('getSupportedMimeTypes')
            ->willReturn([$mime => 'png']);

        $loader1
            ->expects($this->once())
            ->method('load')
            ->with($imagick, $blob, $mime)
            ->willReturn(false);

        $loader2 = $this->createMock(InputLoaderInterface::class);
        $loader2
            ->expects($this->once())
            ->method('getSupportedMimeTypes')
            ->willReturn([$mime => 'png']);

        $loader2
            ->expects($this->once())
            ->method('load')
            ->with($imagick, $blob, $mime)
            ->willReturn(null);

        $this->manager
            ->setImagick($imagick)
            ->registerLoader($loader2)
            ->registerLoader($loader1);

        $this->assertSame(
            $imagick,
            $this->manager->load($mime, $blob),
        );
    }

    public function testManagerReturnsFalseWhenNoLoaderManagesToLoadTheImage(): void
    {
        $loader = $this->createConfiguredStub(InputLoaderInterface::class, [
            'getSupportedMimeTypes' => ['image/png' => 'png'],
            'load' => false,
        ]);

        $this->assertFalse(
            $this->manager
                ->setImagick($this->createStub(Imagick::class))
                ->registerLoader($loader)
                ->load('image/png', 'some data'),
        );
    }

    public function testManagerReturnsNullWhenNoLoadersExist(): void
    {
        $this->assertNull($this->manager->load('image/png', 'some data'));
    }
}
