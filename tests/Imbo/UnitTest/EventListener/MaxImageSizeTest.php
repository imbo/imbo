<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\EventListener;

use Imbo\EventListener\MaxImageSize;

/**
 * @covers Imbo\EventListener\MaxImageSize
 * @group unit
 */
class MaxImageSizeTest extends ListenerTests {
    /**
     * @var MaxImageSize
     */
    private $listener;

    /**
     * Set up the listener
     */
    public function setUp() {
        $this->listener = new MaxImageSize();
    }

    /**
     * Tear down the listener
     */
    public function tearDown() {
        $this->listener = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getListener() {
        return $this->listener;
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getImageDimensions() {
        return array(
            'below limit' => array(100, 100, 200, 200, false),
            'width above' => array(300, 100, 200, 200, true),
            'height above' => array(100, 300, 200, 200, true),
            'both above' => array(300, 300, 200, 200, true),
        );
    }

    /**
     * @dataProvider getImageDimensions
     * @covers Imbo\EventListener\MaxImageSize::enforceMaxSize
     */
    public function testWillTriggerTransformationWhenImageIsAboveTheLimits($imageWidth, $imageHeight, $maxWidth, $maxHeight, $willTrigger) {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getWidth')->will($this->returnValue($imageWidth));
        $image->expects($this->once())->method('getHeight')->will($this->returnValue($imageHeight));

        $request = $this->getMock('Imbo\Http\Request\Request');
        $request->expects($this->once())->method('getImage')->will($this->returnValue($image));

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->once())->method('getRequest')->will($this->returnValue($request));

        if ($willTrigger) {
            $eventManager = $this->getMock('Imbo\EventManager\EventManager');
            $eventManager->expects($this->once())->method('trigger')->with('image.transformation.maxsize', array('image' => $image, 'params' => array('width' => $maxWidth, 'height' => $maxHeight)));
            $event->expects($this->once())->method('getManager')->will($this->returnValue($eventManager));
        }

        $listener = new MaxImageSize($maxWidth, $maxHeight);
        $listener->enforceMaxSize($event);
    }
}
