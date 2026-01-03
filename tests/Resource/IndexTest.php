<?php declare(strict_types=1);
namespace Imbo\Resource;

use Imbo\EventManager\EventInterface;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Model\ArrayModel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[CoversClass(Index::class)]
class IndexTest extends ResourceTests
{
    private Index $resource;
    private Response&MockObject $response;
    private EventInterface&Stub $event;

    protected function getNewResource(): Index
    {
        return new Index();
    }

    public function setUp(): void
    {
        $this->response = $this->createMock(Response::class);
        $this->event = $this->createConfiguredStub(EventInterface::class, [
            'getRequest' => $this->createStub(Request::class),
            'getResponse' => $this->response,
        ]);

        $this->resource = $this->getNewResource();
    }

    public function testSupportsHttpGet(): void
    {
        $this->response
            ->expects($this->once())
            ->method('setModel')
            ->with($this->isInstanceOf(ArrayModel::class));
        $this->response
            ->expects($this->once())
            ->method('setMaxAge')
            ->with(0)
            ->willReturnSelf();
        $this->response
            ->expects($this->once())
            ->method('setPrivate');
        $this->event
            ->method('getConfig')
            ->willReturn(['indexRedirect' => null]);

        $responseHeaders = $this->createMock(ResponseHeaderBag::class);
        $responseHeaders
            ->expects($this->once())
            ->method('addCacheControlDirective')
            ->with('no-store');

        $this->response->headers = $responseHeaders;

        $this->resource->get($this->event);
    }

    public function testRedirectsIfConfigurationOptionHasBeenSet(): void
    {
        $url = 'http://imbo.io';
        $this->event
            ->method('getConfig')
            ->willReturn(['indexRedirect' => $url]);

        $responseHeaders = $this->createMock(ResponseHeaderBag::class);
        $responseHeaders
            ->expects($this->once())
            ->method('set')
            ->with('Location', $url);

        $this->response->headers = $responseHeaders;
        $this->response
            ->expects($this->once())
            ->method('setStatusCode')
            ->with(Response::HTTP_TEMPORARY_REDIRECT);
        $this->response
            ->expects($this->never())
            ->method('setModel');

        $this->resource->get($this->event);
    }
}
