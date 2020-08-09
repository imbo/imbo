<?php declare(strict_types=1);
namespace Imbo\Resource;

use Imbo\Exception\ResourceException;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Database\DatabaseInterface;
use Imbo\EventManager\EventManager;
use Imbo\EventManager\EventInterface;
use Imbo\Router\Route;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * @coversDefaultClass Imbo\Resource\GlobalShortUrl
 */
class GlobalShortUrlTest extends ResourceTests {
    private $resource;
    private $request;
    private $response;
    private $database;
    private $manager;
    private $event;

    protected function getNewResource() : GlobalShortUrl {
        return new GlobalShortUrl();
    }

    public function setUp() : void {
        $this->request = $this->createMock(Request::class);
        $this->response = $this->createMock(Response::class);
        $this->database = $this->createMock(DatabaseInterface::class);
        $this->manager = $this->createMock(EventManager::class);

        $this->event = $this->createConfiguredMock(EventInterface::class, [
            'getRequest' => $this->request,
            'getResponse' => $this->response,
            'getDatabase' => $this->database,
            'getManager' => $this->manager,
        ]);

        $this->resource = $this->getNewResource();
    }

    /**
     * @covers ::getImage
     */
    public function testCanTriggerAnImageGetEventWhenRequestedWithAValidShortUrl() : void {
        $id = 'aaaaaaa';
        $user = 'christer';
        $imageIdentifier = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
        $extension = 'png';
        $query = [
            't' => [
                'thumbnail:width=40'
            ],
            'accessToken' => 'some token',
        ];

        $route = $this->createMock(Route::class);
        $route
            ->method('get')
            ->with('shortUrlId')
            ->willReturn($id);

        $route
            ->method('set')
            ->withConsecutive(
                ['user', $user],
                ['imageIdentifier', $imageIdentifier],
                ['extension', $extension],
            );

        $this->request
            ->expects($this->once())
            ->method('getRoute')
            ->willReturn($route);

        $this->request
            ->expects($this->once())
            ->method('getUri')
            ->willReturn(sprintf('http://imbo/s/%s', $id));

        $this->database
            ->expects($this->once())
            ->method('getShortUrlParams')
            ->with($id)
            ->willReturn([
                'user' => $user,
                'imageIdentifier' => $imageIdentifier,
                'extension' => $extension,
                'query' => $query,
            ]);

        $responseHeaders = $this->createMock(ResponseHeaderBag::class);
        $responseHeaders
            ->expects($this->once())
            ->method('set')
            ->with('X-Imbo-ShortUrl', sprintf('http://imbo/s/%s', $id));

        $this->response->headers = $responseHeaders;

        $this->manager
            ->expects($this->once())
            ->method('trigger')
            ->with('image.get');

        $this->resource->getImage($this->event);
    }

    /**
     * @covers ::getImage
     */
    public function testRespondsWith404WhenShortUrlDoesNotExist() : void {
        $route = $this->createMock(Route::class);
        $route
            ->expects($this->once())
            ->method('get')
            ->with('shortUrlId')
            ->willReturn('aaaaaaa');

        $this->request
            ->expects($this->once())
            ->method('getRoute')
            ->willReturn($route);

        $this->database
            ->expects($this->once())
            ->method('getShortUrlParams')
            ->with('aaaaaaa')
            ->willReturn(null);

        $this->expectExceptionObject(new ResourceException('Image not found', 404));

        $this->resource->getImage($this->event);
    }
}
