<?php declare(strict_types=1);
namespace Imbo\Resource;

use Imbo\Database\DatabaseInterface;
use Imbo\EventManager\EventInterface;
use Imbo\EventManager\EventManager;
use Imbo\Exception\ResourceException;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Router\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[CoversClass(GlobalShortUrl::class)]
class GlobalShortUrlTest extends ResourceTests
{
    private GlobalShortUrl $resource;
    private Request&MockObject $request;
    private Response&MockObject $response;
    private DatabaseInterface&MockObject $database;
    private EventManager&MockObject $manager;
    private EventInterface&MockObject $event;

    protected function getNewResource(): GlobalShortUrl
    {
        return new GlobalShortUrl();
    }

    public function setUp(): void
    {
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

    public function testCanTriggerAnImageGetEventWhenRequestedWithAValidShortUrl(): void
    {
        $id = 'aaaaaaa';
        $user = 'christer';
        $imageIdentifier = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
        $extension = 'png';
        $query = [
            't' => [
                'thumbnail:width=40',
            ],
            'accessToken' => 'some token',
        ];

        /** @var Route&MockObject */
        $route = $this->createMock(Route::class);
        $route
            ->method('get')
            ->with('shortUrlId')
            ->willReturn($id);

        $route
            ->method('set')
            ->with(
                $this->callback(
                    static function (string $name): bool {
                        /** @var int */
                        static $i = 0;
                        return match ([$i++, $name]) {
                            [0, 'user'],
                            [1, 'imageIdentifier'],
                            [2, 'extension'] => true,
                        };
                    },
                ),
                $this->callback(
                    static function (string $value): bool {
                        /** @var int */
                        static $i = 0;
                        return match ([$i++, $value]) {
                            [0, 'christer'],
                            [1, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'],
                            [2, 'png'] => true,
                        };
                    },
                ),
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

        /** @var ResponseHeaderBag&MockObject */
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

    public function testRespondsWith404WhenShortUrlDoesNotExist(): void
    {
        /** @var Route&MockObject */
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

        $this->expectExceptionObject(new ResourceException('Image not found', Response::HTTP_NOT_FOUND));

        $this->resource->getImage($this->event);
    }
}
