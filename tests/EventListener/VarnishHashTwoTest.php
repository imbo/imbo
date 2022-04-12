<?php declare(strict_types=1);
namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Model\Image;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * @coversDefaultClass Imbo\EventListener\VarnishHashTwo
 */
class VarnishHashTwoTest extends ListenerTests
{
    private $listener;
    private $event;
    private $request;
    private $response;
    private $responseHeaders;

    public function setUp(): void
    {
        $this->request = $this->createMock(Request::class);

        $this->responseHeaders = $this->createMock(ResponseHeaderBag::class);
        $this->response = $this->createMock(Response::class);
        $this->response->headers = $this->responseHeaders;

        $this->event = $this->createConfiguredMock(EventInterface::class, [
            'getRequest' => $this->request,
            'getResponse' => $this->response,
        ]);

        $this->listener = new VarnishHashTwo();
    }

    protected function getListener(): VarnishHashTwo
    {
        return $this->listener;
    }

    /**
     * @covers ::addHeader
     */
    public function testCanSendAHashTwoHeader(): void
    {
        $this->request
            ->expects($this->once())
            ->method('getUser')
            ->willReturn('user');

        $image = $this->createConfiguredMock(Image::class, [
            'getImageIdentifier' => 'id',
        ]);

        $this->response
            ->expects($this->once())
            ->method('getModel')
            ->willReturn($image);

        $this->responseHeaders
            ->expects($this->once())
            ->method('set')
            ->with('X-HashTwo', [
                'imbo;image;user;id',
                'imbo;user;user',
            ]);

        $this->listener->addHeader($this->event);
    }

    /**
     * @covers ::__construct
     * @covers ::addHeader
     */
    public function testCanSpecifyACustomHeaderName(): void
    {
        $listener = new VarnishHashTwo(['headerName' => 'X-CustomHeader']);

        $this->request
            ->expects($this->once())
            ->method('getUser')
            ->willReturn('user');

        $image = $this->createConfiguredMock(Image::class, [
            'getImageIdentifier' => 'id',
        ]);

        $this->response
            ->expects($this->once())
            ->method('getModel')
            ->willReturn($image);

        $this->responseHeaders
            ->expects($this->once())
            ->method('set')
            ->with('X-CustomHeader', [
                'imbo;image;user;id',
                'imbo;user;user',
            ]);

        $listener->addHeader($this->event);
    }
}
