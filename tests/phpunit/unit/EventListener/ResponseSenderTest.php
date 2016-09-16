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

use Imbo\EventListener\ResponseSender;

/**
 * @covers Imbo\EventListener\ResponseSender
 * @group unit
 * @group listeners
 */
class ResponseSenderTest extends ListenerTests {
    /**
     * @var ResponseSender
     */
    private $listener;

    /**
     * Set up the listener
     */
    public function setUp() {
        $this->listener = new ResponseSender();
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
     * @covers Imbo\EventListener\ResponseSender::send
     */
    public function testCanSendTheResponse() {
        $image = $this->getMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('checksum'));

        $request = $this->getMock('Imbo\Http\Request\Request');
        $request->expects($this->once())->method('getImage')->will($this->returnValue($image));

        $response = $this->getMock('Imbo\Http\Response\Response');
        $response->expects($this->once())->method('isNotModified')->with($request);
        $response->expects($this->once())->method('send');
        $response->headers = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');
        $response->headers->expects($this->once())->method('set')->with('X-Imbo-ImageIdentifier', 'checksum');

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->any())->method('getRequest')->will($this->returnValue($request));
        $event->expects($this->any())->method('getResponse')->will($this->returnValue($response));

        $this->listener->send($event);
    }

    /**
     * @covers Imbo\EventListener\ResponseSender::send
     */
    public function testCanSendTheResponseAndInjectTheCorrectImageIdentifier() {
        $request = $this->getMock('Imbo\Http\Request\Request');
        $request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('checksum'));

        $response = $this->getMock('Imbo\Http\Response\Response');
        $response->expects($this->once())->method('isNotModified')->with($request);
        $response->expects($this->once())->method('send');
        $response->headers = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');
        $response->headers->expects($this->once())->method('set')->with('X-Imbo-ImageIdentifier', 'checksum');

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->any())->method('getRequest')->will($this->returnValue($request));
        $event->expects($this->any())->method('getResponse')->will($this->returnValue($response));

        $this->listener->send($event);
    }
}
