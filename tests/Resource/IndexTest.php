<?php declare(strict_types=1);
namespace Imbo\Resource;

use Imbo\EventManager\EventInterface;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Model\ArrayModel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[CoversClass(Index::class)]
class IndexTest extends ResourceTests
{
    private Index $resource;
    private Request&MockObject $request;
    private Response&MockObject $response;
    private EventInterface&MockObject $event;

    protected function getNewResource(): Index
    {
        return new Index();
    }

    public function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
        $this->response = $this->createMock(Response::class);
        $this->event = $this->createConfiguredMock(EventInterface::class, [
            'getRequest' => $this->request,
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
            ->expects($this->any())
            ->method('getConfig')
            ->willReturn(['indexRedirect' => null]);

        /** @var ResponseHeaderBag&MockObject */
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
            ->expects($this->any())
            ->method('getConfig')
            ->willReturn(['indexRedirect' => $url]);

        /** @var ResponseHeaderBag&MockObject */
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
