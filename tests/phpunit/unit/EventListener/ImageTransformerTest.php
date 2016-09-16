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

    /**
     * Set up the listener
     */
    public function setUp() {
        $this->eventManager = $this->getMock('Imbo\EventManager\EventManager');
        $this->request = $this->getMock('Imbo\Http\Request\Request');
        $this->image = $this->getMock('Imbo\Model\Image');
        $this->response = $this->getMock('Imbo\Http\Response\Response');
        $this->response->expects($this->any())->method('getModel')->will($this->returnValue($this->image));
        $this->event = $this->getMock('Imbo\EventManager\Event');
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->event->expects($this->any())->method('getManager')->will($this->returnValue($this->eventManager));

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

        $this->eventManager->expects($this->at(0))
                           ->method('trigger')
                           ->with(
                               'image.transformation.resize',
                               [
                                   'image' => $this->image,
                                   'params' => [
                                       'width' => 100,
                                   ],
                               ]
                           );
        $this->eventManager->expects($this->at(1))
                           ->method('trigger')
                           ->with(
                               'image.transformation.thumbnail',
                               [
                                   'image' => $this->image,
                                   'params' => [
                                       'some' => 'value',
                                   ],
                               ]
                           );

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

        $this->eventManager->expects($this->at(0))
                           ->method('trigger')
                           ->with(
                               'image.transformation.fliphorizontally',
                               [
                                   'image' => $this->image,
                                   'params' => [],
                               ]
                           );
        $this->eventManager->expects($this->at(1))
                           ->method('trigger')
                           ->with(
                               'image.transformation.flipvertically',
                               [
                                   'image' => $this->image,
                                   'params' => [],
                               ]
                           );

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

        $this->eventManager->expects($this->once())
                           ->method('trigger')
                           ->with(
                               'image.transformation.thumbnail',
                               [
                                   'image' => $this->image,
                                   'params' => [
                                       'width' => '100',
                                       'height' => 75,
                                   ],
                               ]
                           );
        $this->listener->transform($this->event);
    }
}
