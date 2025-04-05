<?php declare(strict_types=1);
namespace Imbo\Resource;

use Imbo\Database\DatabaseInterface;
use Imbo\EventManager\EventInterface;
use Imbo\Exception\ResourceException;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Model\ArrayModel;
use Imbo\Router\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(ShortUrl::class)]
class ShortUrlTest extends ResourceTests
{
    private Request&MockObject $request;
    private Route&MockObject $route;
    private Response&MockObject $response;
    private DatabaseInterface&MockObject $database;
    private EventInterface&MockObject $event;

    protected function getNewResource(): ShortUrl
    {
        return new ShortUrl();
    }

    public function setUp(): void
    {
        $this->route = $this->createMock(Route::class);
        $this->request = $this->createConfiguredMock(Request::class, [
            'getRoute' => $this->route,
            'getUser' => 'user',
            'getImageIdentifier' => 'id',
        ]);
        $this->response = $this->createMock(Response::class);
        $this->database = $this->createMock(DatabaseInterface::class);
        $this->event = $this->createConfiguredMock(EventInterface::class, [
            'getRequest' => $this->request,
            'getResponse' => $this->response,
            'getDatabase' => $this->database,
        ]);
    }

    public function testThrowsAnExceptionWhenTheShortUrlDoesNotExistWhenDeleting(): void
    {
        $this->route
            ->expects($this->once())
            ->method('get')
            ->with('shortUrlId')
            ->willReturn('aaaaaaa');
        $this->database
            ->expects($this->once())
            ->method('getShortUrlParams')
            ->with('aaaaaaa')
            ->willReturn(null);

        $this->expectExceptionObject(new ResourceException('ShortURL not found', Response::HTTP_NOT_FOUND));
        $this->getNewResource()->deleteShortUrl($this->event);
    }

    public function testThrowsAnExceptionWhenUserOrImageIdentifierDoesNotMatchWhenDeleting(): void
    {
        $this->route
            ->expects($this->once())
            ->method('get')
            ->with('shortUrlId')
            ->willReturn('aaaaaaa');
        $this->database
            ->expects($this->once())
            ->method('getShortUrlParams')
            ->with('aaaaaaa')
            ->willReturn([
                'user' => 'otheruser',
                'imageIdentifier' => 'id',
            ]);

        $this->expectExceptionObject(new ResourceException('ShortURL not found', Response::HTTP_NOT_FOUND));
        $this->getNewResource()->deleteShortUrl($this->event);
    }

    public function testCanDeleteAShortUrl(): void
    {
        $this->route
            ->expects($this->once())
            ->method('get')
            ->with('shortUrlId')
            ->willReturn('aaaaaaa');
        $this->database
            ->expects($this->once())
            ->method('getShortUrlParams')
            ->with('aaaaaaa')
            ->willReturn([
                'user' => 'user',
                'imageIdentifier' => 'id',
            ]);
        $this->database
            ->expects($this->once())
            ->method('deleteShortUrls')
            ->with('user', 'id', 'aaaaaaa');
        $this->response
            ->expects($this->once())
            ->method('setModel')
            ->with($this->isInstanceOf(ArrayModel::class));

        $this->getNewResource()->deleteShortUrl($this->event);
    }

    public function testThrowsAnExceptionWhenTheShortUrlDoesNotExistWhenGetting(): void
    {
        $this->route
            ->expects($this->once())
            ->method('get')
            ->with('shortUrlId')
            ->willReturn('aaaaaaa');
        $this->database
            ->expects($this->once())
            ->method('getShortUrlParams')
            ->with('aaaaaaa')
            ->willReturn(null);

        $this->expectExceptionObject(new ResourceException('ShortURL not found', Response::HTTP_NOT_FOUND));
        $this->getNewResource()->getShortUrl($this->event);
    }

    public function testThrowsAnExceptionWhenUserOrImageIdentifierDoesNotMatchWhenGetting(): void
    {
        $this->route
            ->expects($this->once())
            ->method('get')
            ->with('shortUrlId')
            ->willReturn('aaaaaaa');
        $this->database
            ->expects($this->once())
            ->method('getShortUrlParams')
            ->with('aaaaaaa')
            ->willReturn([
                'user' => 'otheruser',
                'imageIdentifier' => 'id',
            ]);

        $this->expectExceptionObject(new ResourceException('ShortURL not found', Response::HTTP_NOT_FOUND));
        $this->getNewResource()->getShortUrl($this->event);
    }

    public function testCanGetAShortUrl(): void
    {
        $this->route
            ->expects($this->once())
            ->method('get')
            ->with('shortUrlId')
            ->willReturn('aaaaaaa');

        $params = [
            'user' => 'user',
            'imageIdentifier' => 'id',
            'extension' => 'gif',
            'query' => [
                't' => [
                    'thumbnail',
                ],
            ],
        ];

        $this->database
            ->expects($this->once())
            ->method('getShortUrlParams')
            ->with('aaaaaaa')
            ->willReturn($params);

        $this->response
            ->expects($this->once())
            ->method('setModel')
            ->with($this->callback(fn (ArrayModel $model): bool => $model->getData() === $params));

        $this->getNewResource()->getShortUrl($this->event);
    }
}
