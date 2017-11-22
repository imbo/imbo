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

use Imbo\EventListener\MaxImageSize;

/**
 * @covers Imbo\EventListener\MaxImageSize
 * @group unit
 * @group listeners
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
        $this->listener = new MaxImageSize([]);
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
        return [
            'below limit' => [100, 100, 200, 200, false],
            'width above' => [300, 100, 200, 200, true],
            'height above' => [100, 300, 200, 200, true],
            'both above' => [300, 300, 200, 200, true],
        ];
    }

    /**
     * @dataProvider getImageDimensions
     * @covers Imbo\EventListener\MaxImageSize::enforceMaxSize
     */
    public function testWillTriggerTransformationWhenImageIsAboveTheLimits($imageWidth, $imageHeight, $maxWidth, $maxHeight, $willTrigger) {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getWidth')->will($this->returnValue($imageWidth));
        $image->expects($this->once())->method('getHeight')->will($this->returnValue($imageHeight));

        $request = $this->createMock('Imbo\Http\Request\Request');
        $request->expects($this->once())->method('getImage')->will($this->returnValue($image));

        $event = $this->createMock('Imbo\EventManager\Event');
        $event->expects($this->once())->method('getRequest')->will($this->returnValue($request));

        if ($willTrigger) {
            $maxSize = $this->createMock('Imbo\Image\Transformation\MaxSize');
            $maxSize->expects($this->once())->method('setImage')->will($this->returnSelf());
            $maxSize->expects($this->once())->method('transform')->with(['width' => $maxWidth, 'height' => $maxHeight]);

            $transformationManager = $this->createMock('Imbo\Image\TransformationManager');
            $transformationManager
                ->expects($this->once())
                ->method('getTransformation')
                ->with('maxSize')
                ->will($this->returnValue($maxSize));

            $event->expects($this->once())->method('getTransformationManager')->will($this->returnValue($transformationManager));
        }

        $listener = new MaxImageSize(['width' => $maxWidth, 'height' => $maxHeight]);
        $listener->enforceMaxSize($event);
    }
}
