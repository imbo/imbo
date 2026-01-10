<?php declare(strict_types=1);

namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Model\Image;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[CoversClass(VarnishHashTwo::class)]
class VarnishHashTwoTest extends ListenerTests
{
    private VarnishHashTwo $listener;
    private EventInterface $event;
    private Request&MockObject $request;
    private Response&MockObject $response;
    private ResponseHeaderBag&MockObject $responseHeaders;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Request::class);

        $this->responseHeaders = $this->createMock(ResponseHeaderBag::class);
        $this->response = $this->createMock(Response::class);
        $this->response->headers = $this->responseHeaders;

        $this->event = $this->createConfiguredStub(EventInterface::class, [
            'getRequest' => $this->request,
            'getResponse' => $this->response,
        ]);

        $this->listener = new VarnishHashTwo();
    }

    protected function getListener(): VarnishHashTwo
    {
        return $this->listener;
    }

    public function testCanSendAHashTwoHeader(): void
    {
        $this->request
            ->expects($this->once())
            ->method('getUser')
            ->willReturn('user');

        $image = $this->createConfiguredStub(Image::class, [
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

    public function testCanSpecifyACustomHeaderName(): void
    {
        $listener = new VarnishHashTwo(['headerName' => 'X-CustomHeader']);

        $this->request
            ->expects($this->once())
            ->method('getUser')
            ->willReturn('user');

        $image = $this->createConfiguredStub(Image::class, [
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
