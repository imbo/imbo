<?php declare(strict_types=1);

namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface;
use Imbo\Http\Request\Request;
use Imbo\Image\Transformation\MaxSize;
use Imbo\Image\TransformationManager;
use Imbo\Model\Image;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(MaxImageSize::class)]
class MaxImageSizeTest extends ListenerTests
{
    private MaxImageSize $listener;

    protected function setUp(): void
    {
        $this->listener = new MaxImageSize([]);
    }

    protected function getListener(): MaxImageSize
    {
        return $this->listener;
    }

    #[DataProvider('getImageDimensions')]
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

    /**
     * @return array<array{imageWidth:int,imageHeight:int,maxWidth:int,maxHeight:int,willTrigger:bool}>
     */
    public static function getImageDimensions(): array
    {
        return [
            'below limit' => [
                'imageWidth' => 100,
                'imageHeight' => 100,
                'maxWidth' => 200,
                'maxHeight' => 200,
                'willTrigger' => false,
            ],
            'width above' => [
                'imageWidth' => 300,
                'imageHeight' => 100,
                'maxWidth' => 200,
                'maxHeight' => 200,
                'willTrigger' => true,
            ],
            'height above' => [
                'imageWidth' => 100,
                'imageHeight' => 300,
                'maxWidth' => 200,
                'maxHeight' => 200,
                'willTrigger' => true,
            ],
            'both above' => [
                'imageWidth' => 300,
                'imageHeight' => 300,
                'maxWidth' => 200,
                'maxHeight' => 200,
                'willTrigger' => true,
            ],
        ];
    }
}
