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

use Imbo\EventListener\ImageTransformer,
    Imbo\Image\Transformation\Transformation,
    Imbo\Storage\ImageReaderAware,
    Imbo\Storage\ImageReaderAwareTrait,
    Imbo\Model\Image;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 * @covers Imbo\EventListener\ImageTransformer
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
        $this->event->expects($this->once())->method('getConfig')->will($this->returnValue(array('transformationPresets' => array())));
        $this->request->expects($this->once())->method('getTransformations')->will($this->returnValue(array(
            array(
                'name' => 'resize',
                'params' => array(
                    'width' => 100,
                ),
            ),
            array(
                'name' => 'thumbnail',
                'params' => array(
                    'some' => 'value',
                ),
            ),
        )));

        $this->eventManager->expects($this->at(0))
                           ->method('trigger')
                           ->with(
                               'image.transformation.resize',
                               array(
                                   'image' => $this->image,
                                   'params' => array(
                                       'width' => 100,
                                   ),
                               )
                           );
        $this->eventManager->expects($this->at(1))
                           ->method('trigger')
                           ->with(
                               'image.transformation.thumbnail',
                               array(
                                   'image' => $this->image,
                                   'params' => array(
                                       'some' => 'value',
                                   ),
                               )
                           );

        $this->listener->transform($this->event);
    }

    /**
     * @covers Imbo\EventListener\ImageTransformer::transform
     */
    public function testSupportsPresets() {
        $this->event->expects($this->once())->method('getConfig')->will($this->returnValue(array(
            'transformationPresets' => array(
                'preset' => array(
                    'flipHorizontally',
                    'flipVertically',
                ),
            )
        )));
        $this->request->expects($this->once())->method('getTransformations')->will($this->returnValue(array(
            array(
                'name' => 'preset',
                'params' => array(),
            ),
        )));

        $this->eventManager->expects($this->at(0))
                           ->method('trigger')
                           ->with(
                               'image.transformation.fliphorizontally',
                               array(
                                   'image' => $this->image,
                                   'params' => array(),
                               )
                           );
        $this->eventManager->expects($this->at(1))
                           ->method('trigger')
                           ->with(
                               'image.transformation.flipvertically',
                               array(
                                   'image' => $this->image,
                                   'params' => array(),
                               )
                           );

        $this->listener->transform($this->event);
    }
}
