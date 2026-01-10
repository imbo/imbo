<?php declare(strict_types=1);

namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface;
use Imbo\Http\Request\Request;
use Imbo\Image\Transformation\AutoRotate;
use Imbo\Image\TransformationManager;
use Imbo\Model\Image;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function get_class;

#[CoversClass(AutoRotateImage::class)]
class AutoRotateImageTest extends TestCase
{
    private AutoRotateImage $listener;

    protected function setUp(): void
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
        $image = $this->createStub(Image::class);

        $request = $this->createConfiguredStub(Request::class, [
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

        $event = $this->createConfiguredStub(EventInterface::class, [
            'getRequest' => $request,
            'getTransformationManager' => $transformationManager,
        ]);

        $this->listener->autoRotate($event);
    }
}
