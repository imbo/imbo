<?php declare(strict_types=1);
namespace ImboUnitTest\EventListener;

use Imbo\EventListener\VarnishHashTwo;

/**
 * @coversDefaultClass Imbo\EventListener\VarnishHashTwo
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
    public function setUp() : void {
        $this->request = $this->createMock('Imbo\Http\Request\Request');

        $this->responseHeaders = $this->createMock('Symfony\Component\HttpFoundation\ResponseHeaderBag');
        $this->response = $this->createMock('Imbo\Http\Response\Response');
        $this->response->headers = $this->responseHeaders;

        $this->event = $this->createMock('Imbo\EventManager\Event');
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));

        $this->listener = new VarnishHashTwo();
    }

    protected function getListener() : VarnishHashTwo {
        return $this->listener;
    }

    /**
     * @covers ::addHeader
     */
    public function testCanSendAHashTwoHeader() : void {
        $this->request->expects($this->once())->method('getUser')->will($this->returnValue('user'));
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('id'));
        $this->response->expects($this->once())->method('getModel')->will($this->returnValue($image));
        $this->responseHeaders->expects($this->once())->method('set')->with('X-HashTwo', [
            'imbo;image;user;id',
            'imbo;user;user',
        ]);

        $this->listener->addHeader($this->event);
    }

    /**
     * @covers ::__construct
     * @covers ::addHeader
     */
    public function testCanSpecifyACustomHeaderName() : void {
        $listener = new VarnishHashTwo(['headerName' => 'X-CustomHeader']);

        $this->request->expects($this->once())->method('getUser')->will($this->returnValue('user'));
        $image = $this->createMock('Imbo\Model\Image');
        $image->expects($this->once())->method('getImageIdentifier')->will($this->returnValue('id'));
        $this->response->expects($this->once())->method('getModel')->will($this->returnValue($image));
        $this->responseHeaders->expects($this->once())->method('set')->with('X-CustomHeader', [
            'imbo;image;user;id',
            'imbo;user;user',
        ]);

        $listener->addHeader($this->event);
    }
}
