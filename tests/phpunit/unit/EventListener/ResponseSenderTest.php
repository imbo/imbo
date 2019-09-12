<?php
namespace ImboUnitTest\EventListener;

use Imbo\EventListener\ResponseSender;

/**
 * @coversDefaultClass Imbo\EventListener\ResponseSender
 */
class ResponseSenderTest extends ListenerTests {
    /**
     * @var ResponseSender
     */
    private $listener;

    /**
     * Set up the listener
     */
    public function setUp() : void {
        $this->listener = new ResponseSender();
    }

    /**
     * {@inheritdoc}
     */
    protected function getListener() {
        return $this->listener;
    }

    /**
     * @covers ::send
     */
    public function testCanSendTheResponse() {
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('checksum'));

        $request = $this->createMock('Imbo\Http\Request\Request');
        $request->expects($this->once())->method('getImage')->will($this->returnValue($image));

        $response = $this->createMock('Imbo\Http\Response\Response');
        $response->expects($this->once())->method('isNotModified')->with($request);
        $response->expects($this->once())->method('send');
        $response->headers = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
        $response->headers->expects($this->once())->method('set')->with('X-Imbo-ImageIdentifier', 'checksum');

        $event = $this->createMock('Imbo\EventManager\Event');
        $event->expects($this->any())->method('getRequest')->will($this->returnValue($request));
        $event->expects($this->any())->method('getResponse')->will($this->returnValue($response));

        $this->listener->send($event);
    }

    /**
     * @covers ::send
     */
    public function testCanSendTheResponseAndInjectTheCorrectImageIdentifier() {
        $request = $this->createMock('Imbo\Http\Request\Request');
        $request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('checksum'));

        $response = $this->createMock('Imbo\Http\Response\Response');
        $response->expects($this->once())->method('isNotModified')->with($request);
        $response->expects($this->once())->method('send');
        $response->headers = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
        $response->headers->expects($this->once())->method('set')->with('X-Imbo-ImageIdentifier', 'checksum');

        $event = $this->createMock('Imbo\EventManager\Event');
        $event->expects($this->any())->method('getRequest')->will($this->returnValue($request));
        $event->expects($this->any())->method('getResponse')->will($this->returnValue($response));

        $this->listener->send($event);
    }
}
