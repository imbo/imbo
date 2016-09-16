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

/**
 * @covers Imbo\EventListener\AutoRotateImage
 * @group unit
 * @group listeners
 */
class AutoRotateImageTest extends \PHPUnit_Framework_TestCase {
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
     * Tear down the listener
     */
    public function tearDown() {
        $this->listener = null;
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
    public function testTriggersAnEventForRotatingTheImage() {
        $image = $this->getMock('Imbo\Model\Image');

        $request = $this->getMock('Imbo\Http\Request\Request');
        $request->expects($this->once())->method('getImage')->will($this->returnValue($image));

        $eventManager = $this->getMock('Imbo\EventManager\EventManager');
        $eventManager->expects($this->once())->method('trigger')->with('image.transformation.autorotate', ['image' => $image]);

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->once())->method('getRequest')->will($this->returnValue($request));
        $event->expects($this->once())->method('getManager')->will($this->returnValue($eventManager));

        $this->listener->autoRotate($event);
    }
}
