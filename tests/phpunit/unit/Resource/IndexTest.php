<?php declare(strict_types=1);
namespace Imbo\Resource;

use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\EventManager\EventInterface;
use Imbo\Model\ArrayModel;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * @coversDefaultClass Imbo\Resource\Index
 */
class IndexTest extends ResourceTests {
    private $resource;
    private $request;
    private $response;
    private $event;
    private $responseHeaders;

    protected function getNewResource() : Index {
        return new Index();
    }

    public function setUp() : void {
        $this->request = $this->createMock(Request::class);
        $this->response = $this->createMock(Response::class);
        $this->responseHeaders = $this->createMock(ResponseHeaderBag::class);
        $this->event = $this->createConfiguredMock(EventInterface::class, [
            'getRequest' => $this->request,
            'getResponse' => $this->response,
        ]);

        $this->resource = $this->getNewResource();
    }

    /**
     * @covers ::get
     */
    public function testSupportsHttpGet() : void {
        $this->request
            ->expects($this->once())
            ->method('getSchemeAndHttpHost')
            ->willReturn('http://imbo');
        $this->request
            ->expects($this->once())
            ->method('getBaseUrl')
            ->willReturn('');
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

        $responseHeaders = $this->createMock(ResponseHeaderBag::class);
        $responseHeaders
            ->expects($this->once())
            ->method('addCacheControlDirective')
            ->with('no-store');

        $this->response->headers = $responseHeaders;

        $this->resource->get($this->event);
    }

    /**
     * @covers ::get
     */
    public function testRedirectsIfConfigurationOptionHasBeenSet() : void {
        $url = 'http://imbo.io';
        $this->event
            ->expects($this->any())
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
            ->with(307);
        $this->response
            ->expects($this->never())
            ->method('setModel');

        $this->resource->get($this->event);
    }
}
