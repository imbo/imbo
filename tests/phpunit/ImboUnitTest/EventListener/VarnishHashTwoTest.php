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

use Imbo\EventListener\VarnishHashTwo;

/**
 * @covers Imbo\EventListener\VarnishHashTwo
 * @group unit
 * @group listeners
 */
class VarnishHashTwoTest extends ListenerTests {
    /**
     * @var VarnishHashTwo
     */
    private $listener;

    private $event;
    private $request;
    private $response;
    private $responseHeaders;

    /**
     * Set up the listener
     */
    public function setUp() {
        $this->request = $this->getMock('Imbo\Http\Request\Request');

        $this->responseHeaders = $this->getMock('Symfony\Component\HttpFoundation\ResponseHeaderBag');
        $this->response = $this->getMock('Imbo\Http\Response\Response');
        $this->response->headers = $this->responseHeaders;

        $this->event = $this->getMock('Imbo\EventManager\Event');
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));

        $this->listener = new VarnishHashTwo();
    }

    /**
     * {@inheritdoc}
     */
    protected function getListener() {
        return $this->listener;
    }

    /**
     * Tear down the listener
     */
    public function tearDown() {
        $this->request = null;
        $this->response = null;
        $this->responseHeaders = null;
        $this->event = null;
        $this->listener = null;
    }

    /**
     * @covers Imbo\EventListener\VarnishHashTwo::addHeader
     */
    public function testCanSendAHashTwoHeader() {
        $this->request->expects($this->once())->method('getUser')->will($this->returnValue('user'));
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('id'));
        $this->response->expects($this->once())->method('getModel')->will($this->returnValue($image));
        $this->responseHeaders->expects($this->once())->method('set')->with('X-HashTwo', [
            'imbo;image;user;id',
            'imbo;user;user',
        ]);

        $this->listener->addHeader($this->event);
    }

    /**
     * @covers Imbo\EventListener\VarnishHashTwo::__construct
     * @covers Imbo\EventListener\VarnishHashTwo::addHeader
     */
    public function testCanSpecifyACustomHeaderName() {
        $listener = new VarnishHashTwo(['headerName' => 'X-CustomHeader']);

        $this->request->expects($this->once())->method('getUser')->will($this->returnValue('user'));
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('id'));
        $this->response->expects($this->once())->method('getModel')->will($this->returnValue($image));
        $this->responseHeaders->expects($this->once())->method('set')->with('X-CustomHeader', [
            'imbo;image;user;id',
            'imbo;user;user',
        ]);

        $listener->addHeader($this->event);
    }
}
