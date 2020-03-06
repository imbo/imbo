<?php declare(strict_types=1);
namespace Imbo\Resource;

use Imbo\Database\DatabaseInterface;
use Imbo\EventManager\EventInterface;
use Imbo\Exception\ResourceException;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Model\ArrayModel;
use Imbo\Router\Route;

/**
 * @coversDefaultClass Imbo\Resource\ShortUrl
 */
class ShortUrlTest extends ResourceTests {
    private $request;
    private $route;
    private $response;
    private $database;
    private $event;

    protected function getNewResource() : ShortUrl {
        return new ShortUrl();
    }

    public function setUp() : void {
        $this->resource = $this->getNewResource();
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

    /**
     * @covers ::deleteShortUrl
     */
    public function testThrowsAnExceptionWhenTheShortUrlDoesNotExist() : void {
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

        $this->expectExceptionObject(new ResourceException('ShortURL not found', 404));
        $this->getNewResource()->deleteShortUrl($this->event);
    }

    /**
     * @covers ::deleteShortUrl
     */
    public function testThrowsAnExceptionWhenUserOrPrivateKeyDoesNotMatch() : void {
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

        $this->expectExceptionObject(new ResourceException('ShortURL not found', 404));
        $this->getNewResource()->deleteShortUrl($this->event);
    }

    /**
     * @covers ::deleteShortUrl
     */
    public function testCanDeleteAShortUrl() : void {
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
}
