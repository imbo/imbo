<?php declare(strict_types=1);
namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface;
use Imbo\Http\Request\Request;
use Imbo\Image\Transformation\AutoRotate;
use Imbo\Image\TransformationManager;
use Imbo\Model\Image;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AutoRotateImage::class)]
class AutoRotateImageTest extends TestCase
{
    private AutoRotateImage $listener;

    public function setUp(): void
    {
        $this->listener = new AutoRotateImage();
    }

    public function testReturnsCorrectSubscriptionData(): void
    {
        $className = get_class($this->listener);
        $events = $className::getSubscribedEvents();

        $this->assertTrue(isset($events['images.post']['autoRotate']));
    }

    public function testTriggersTransformationForRotating(): void
    {
        $image = $this->createMock(Image::class);

        $request = $this->createConfiguredMock(Request::class, [
            'getImage' => $image,
        ]);

        $autoRotate = $this->createMock(AutoRotate::class);
        $autoRotate
            ->expects($this->once())
            ->method('setImage')
            ->with($image)
            ->willReturnSelf();
        $autoRotate
            ->expects($this->once())
            ->method('transform')
            ->with([]);

        $transformationManager = $this->createMock(TransformationManager::class);
        $transformationManager
            ->expects($this->once())
            ->method('getTransformation')
            ->with('autoRotate')
            ->willReturn($autoRotate);

        $event = $this->createConfiguredMock(EventInterface::class, [
            'getRequest' => $request,
            'getTransformationManager' => $transformationManager,
        ]);

        $this->listener->autoRotate($event);
    }
}
