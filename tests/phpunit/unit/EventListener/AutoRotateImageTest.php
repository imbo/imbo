<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\EventListener;

use Imbo\EventListener\AutoRotateImage;
use PHPUnit\Framework\TestCase;

/**
 * @covers Imbo\EventListener\AutoRotateImage
 * @group unit
 * @group listeners
 */
class AutoRotateImageTest extends TestCase {
    /**
     * @var AutoRotateImage
     */
    private $listener;

    /**
     * Set up the listener
     */
    public function setUp() {
        $this->listener = new AutoRotateImage();
    }

    /**
     * @covers Imbo\EventListener\AutoRotateImage::getSubscribedEvents
     */
    public function testReturnsCorrectSubscriptionData() {
        $className = get_class($this->listener);
        $events = $className::getSubscribedEvents();

        $this->assertTrue(isset($events['images.post']['autoRotate']));

    }

    /**
     * @covers Imbo\EventListener\AutoRotateImage::autoRotate
     */
    public function testTriggersTransformationForRotating() {
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
