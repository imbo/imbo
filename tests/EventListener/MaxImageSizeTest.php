<?php declare(strict_types=1);
namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface;
use Imbo\Http\Request\Request;
use Imbo\Image\Transformation\MaxSize;
use Imbo\Image\TransformationManager;
use Imbo\Model\Image;

/**
 * @coversDefaultClass Imbo\EventListener\MaxImageSize
 */
class MaxImageSizeTest extends ListenerTests
{
    private $listener;

    public function setUp(): void
    {
        $this->listener = new MaxImageSize([]);
    }

    protected function getListener(): MaxImageSize
    {
        return $this->listener;
    }

    public function getImageDimensions(): array
    {
        return [
            'below limit' => [100, 100, 200, 200, false],
            'width above' => [300, 100, 200, 200, true],
            'height above' => [100, 300, 200, 200, true],
            'both above' => [300, 300, 200, 200, true],
        ];
    }

    /**
     * @dataProvider getImageDimensions
     * @covers ::enforceMaxSize
     */
    public function testWillTriggerTransformationWhenImageIsAboveTheLimits(int $imageWidth, int $imageHeight, int $maxWidth, int $maxHeight, bool $willTrigger): void
    {
        $image = $this->createMock(Image::class);
        $image
            ->expects($this->once())
            ->method('getWidth')
            ->willReturn($imageWidth);

        $image
            ->expects($this->once())
            ->method('getHeight')
            ->willReturn($imageHeight);

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getImage')
            ->willReturn($image);

        $event = $this->createMock(EventInterface::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        if ($willTrigger) {
            $maxSize = $this->createMock(MaxSize::class);
            $maxSize
                ->expects($this->once())
                ->method('setImage')
                ->willReturnSelf();

            $maxSize
                ->expects($this->once())
                ->method('transform')
                ->with(['width' => $maxWidth, 'height' => $maxHeight]);

            $transformationManager = $this->createMock(TransformationManager::class);
            $transformationManager
                ->expects($this->once())
                ->method('getTransformation')
                ->with('maxSize')
                ->willReturn($maxSize);

            $event
                ->expects($this->once())
                ->method('getTransformationManager')
                ->willReturn($transformationManager);
        }

        $listener = new MaxImageSize(['width' => $maxWidth, 'height' => $maxHeight]);
        $listener->enforceMaxSize($event);
    }
}
