<?php declare(strict_types=1);
namespace ImboUnitTest\EventListener;

use Imbo\EventListener\AutoRotateImage;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\EventListener\AutoRotateImage
 */
class AutoRotateImageTest extends TestCase {
    /**
     * @var AutoRotateImage
     */
    private $listener;

    /**
     * Set up the listener
     */
    public function setUp() : void {
        $this->listener = new AutoRotateImage();
    }

    /**
     * @covers ::getSubscribedEvents
     */
    public function testReturnsCorrectSubscriptionData() : void {
        $className = get_class($this->listener);
        $events = $className::getSubscribedEvents();

        $this->assertTrue(isset($events['images.post']['autoRotate']));

    }

    /**
     * @covers ::autoRotate
     */
    public function testTriggersTransformationForRotating() : void {
        $image = $this->createMock('Imbo\Model\Image');

        $request = $this->createMock('Imbo\Http\Request\Request');
        $request->expects($this->once())->method('getImage')->will($this->returnValue($image));

        $autoRotate = $this->createMock('Imbo\Image\Transformation\Transformation');
        $autoRotate
            ->expects($this->once())
            ->method('setImage')
            ->with($image)
            ->will($this->returnSelf());

        $autoRotate
            ->expects($this->once())
            ->method('transform')
            ->with([]);

        $transformationManager = $this->createMock('Imbo\Image\TransformationManager');
        $transformationManager
            ->expects($this->once())
            ->method('getTransformation')
            ->with('autoRotate')
            ->will($this->returnValue($autoRotate));

        $event = $this->createMock('Imbo\EventManager\Event');
        $event->expects($this->once())->method('getRequest')->will($this->returnValue($request));
        $event->expects($this->once())->method('getTransformationManager')->will($this->returnValue($transformationManager));

        $this->listener->autoRotate($event);
    }
}
