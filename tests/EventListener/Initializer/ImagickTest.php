<?php declare(strict_types=1);
namespace Imbo\EventListener\Initializer;

use Imagick;
use Imbo\EventListener\Imagick as ImagickEventListener;
use Imbo\EventListener\Initializer\Imagick as ImagickInitializer;
use Imbo\Image\Transformation\Border;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImagickInitializer::class)]
class ImagickTest extends TestCase
{
    public function testInjectsImagickIntoEventListeners(): void
    {
        $imagick = $this->createMock(Imagick::class);

        $listener = $this->createMock(ImagickEventListener::class);
        $listener
            ->expects($this->once())
            ->method('setImagick')
            ->with($imagick);

        (new ImagickInitializer($imagick))->initialize($listener);
    }

    public function testCanCreateAnImagickInstanceByItself(): void
    {
        $listener = $this->createMock(Border::class);
        $listener
            ->expects($this->once())
            ->method('setImagick')
            ->with($this->isInstanceOf(Imagick::class));

        (new ImagickInitializer())->initialize($listener);
    }
}
