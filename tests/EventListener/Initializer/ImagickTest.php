<?php declare(strict_types=1);
namespace Imbo\EventListener\Initializer;

use Imbo\EventListener\Initializer\Imagick as ImagickInitializer;
use Imbo\EventListener\Imagick as ImagickEventListener;
use Imbo\Image\Transformation\Border;
use PHPUnit\Framework\TestCase;
use Imagick;

/**
 * @coversDefaultClass Imbo\EventListener\Initializer\Imagick
 */
class ImagickTest extends TestCase {
    /**
     * @covers ::initialize
     */
    public function testInjectsImagickIntoEventListeners() : void {
        $imagick = $this->createMock(Imagick::class);

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
    public function testCanCreateAnImagickInstanceByItself() : void {
        $listener = $this->createMock(Border::class);
        $listener
            ->expects($this->once())
            ->method('setImagick')
            ->with($this->isInstanceOf(Imagick::class));

        (new ImagickInitializer())->initialize($listener);
    }
}
