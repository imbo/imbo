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

use Imbo\EventListener\ImageTransformer;

/**
 * @covers Imbo\EventListener\ImageTransformer
 * @group unit
 * @group listeners
 */
class ImageTransformerTest extends ListenerTests {
    /**
     * @var ImageTransformer
     */
    private $listener;

    private $request;
    private $response;
    private $event;
    private $image;
    private $eventManager;
    private $transformationManager;

    /**
     * Set up the listener
     */
    public function setUp() {
        $this->transformationManager = $this->getMockBuilder('\Imbo\Image\TransformationManager')->getMock();
        $this->eventManager = $this->getMock('Imbo\EventManager\EventManager');
        $this->request = $this->getMock('Imbo\Http\Request\Request');
        $this->image = $this->getMock('Imbo\Model\Image');
        $this->response = $this->getMock('Imbo\Http\Response\Response');
        $this->response->expects($this->any())->method('getModel')->will($this->returnValue($this->image));
        $this->event = $this->createMock('Imbo\EventManager\Event');
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->event->expects($this->any())->method('getManager')->will($this->returnValue($this->eventManager));
        $this->event->expects($this->any())->method('getTransformationManager')->will($this->returnValue($this->transformationManager));

        $this->listener = new ImageTransformer();
    }

    /**
     * Tear down the listener
     */
    public function tearDown() {
        $this->request = null;
        $this->response = null;
        $this->image = null;
        $this->event = null;
        $this->listener = null;
        $this->eventManager = null;
        $this->transformationManager = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getListener() {
        return $this->listener;
    }

    /**
     * @covers Imbo\EventListener\ImageTransformer::transform
     */
    public function testTriggersImageTransformationEvents() {
        $this->event->expects($this->once())->method('getConfig')->will($this->returnValue(['transformationPresets' => []]));
        $this->request->expects($this->once())->method('getTransformations')->will($this->returnValue([
            [
                'name' => 'resize',
                'params' => [
                    'width' => 100,
                ],
            ],
            [
                'name' => 'thumbnail',
                'params' => [
                    'some' => 'value',
                ],
            ],
        ]));

        $resize = $this->getTransformationMock();
        $resize->expects($this->once())
            ->method('transform')
            ->with(['width' => 100]);

        $thumbnail = $this->getTransformationMock();
        $thumbnail->expects($this->once())
            ->method('transform')
            ->with(['some' => 'value']);

        $this->transformationManager
            ->expects($this->at(0))
            ->method('getTransformation')
            ->with('resize')
            ->will($this->returnValue($resize));

        $this->transformationManager
            ->expects($this->at(1))
            ->method('getTransformation')
            ->with('thumbnail')
            ->will($this->returnValue($thumbnail));

        $this->listener->transform($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageTransformer::transform
     */
    public function testSupportsPresets() {
        $this->event->expects($this->once())->method('getConfig')->will($this->returnValue([
            'transformationPresets' => [
                'preset' => [
                    'flipHorizontally',
                    'flipVertically',
                ],
            ]
        ]));
        $this->request->expects($this->once())->method('getTransformations')->will($this->returnValue([
            [
                'name' => 'preset',
                'params' => [],
            ],
        ]));

        $flip = $this->getTransformationMock();
        $flip->expects($this->once())
            ->method('transform')
            ->with([]);

        $flop = $this->getTransformationMock();
        $flop->expects($this->once())
            ->method('transform')
            ->with([]);

        $this->transformationManager
            ->expects($this->at(0))
            ->method('getTransformation')
            ->with('flipHorizontally')
            ->will($this->returnValue($flip));

        $this->transformationManager
            ->expects($this->at(1))
            ->method('getTransformation')
            ->with('flipVertically')
            ->will($this->returnValue($flop));

        $this->listener->transform($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageTransformer::transform
     */
    public function testPresetsCanHardcodeSomeParameters() {
        $this->event->expects($this->once())->method('getConfig')->will($this->returnValue([
            'transformationPresets' => [
                'preset' => [
                    'thumbnail' => [
                        'height' => 75,
                    ],
                ],
            ]
        ]));
        $this->request->expects($this->once())->method('getTransformations')->will($this->returnValue([
            [
                'name' => 'preset',
                'params' => [
                    'width' => '100',
                    'height' => '200',
                ],
            ],
        ]));

        $thumbnail = $this->getTransformationMock();
        $thumbnail->expects($this->once())
                  ->method('transform')
                  ->with([ 'width' => '100', 'height' => 75 ]);

        $this->transformationManager
            ->expects($this->at(0))
            ->method('getTransformation')
            ->with('thumbnail')
            ->will($this->returnValue($thumbnail));

        $this->listener->transform($this->event);
    }

    protected function getTransformationMock() {
        $transformation = $this->getMock('Imbo\Image\Transformation\Transformation');
        $transformation->expects($this->any())->method('setImage')->will($this->returnSelf());
        $transformation->expects($this->any())->method('setEvent')->will($this->returnSelf());
        return $transformation;
    }
}
