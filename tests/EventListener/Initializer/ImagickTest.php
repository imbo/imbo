<?php declare(strict_types=1);
namespace Imbo\EventListener\Initializer;

use Imagick;
use Imbo\EventListener\Imagick as ImagickEventListener;
use Imbo\EventListener\Initializer\Imagick as ImagickInitializer;
use Imbo\Image\Transformation\Border;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\EventListener\Initializer\Imagick
 */
class ImagickTest extends TestCase
{
    /**
     * @covers ::initialize
     */
    public function testInjectsImagickIntoEventListeners(): void
    {
        /** @var Imagick&MockObject */
        $imagick = $this->createMock(Imagick::class);

        /** @var ImagickEventListener&MockObject */
        $listener = $this->createMock(ImagickEventListener::class);
        $listener
            ->expects($this->once())
            ->method('setImagick')
            ->with($imagick);

        (new ImagickInitializer($imagick))->initialize($listener);
    }

    /**
     * @covers ::__construct
     * @covers ::initialize
     */
    public function testCanCreateAnImagickInstanceByItself(): void
    {
        /** @var Border&MockObject */
        $listener = $this->createMock(Border::class);
        $listener
            ->expects($this->once())
            ->method('setImagick')
            ->with($this->isInstanceOf(Imagick::class));

        (new ImagickInitializer())->initialize($listener);
    }
}
