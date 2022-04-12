<?php declare(strict_types=1);
namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Symfony\Component\HttpFoundation\HeaderBag;

/**
 * @coversDefaultClass Imbo\EventListener\ResponseETag
 */
class ResponseETagTest extends ListenerTests
{
    private $listener;

    public function setUp(): void
    {
        $this->listener = new ResponseETag();
    }

    protected function getListener(): ResponseETag
    {
        return $this->listener;
    }

    public function getTaintedHeaders(): array
    {
        return [
            'non-tainted' => ['"be7d5bb2f29494c0a1c95c81e8ae8b99"', '"be7d5bb2f29494c0a1c95c81e8ae8b99"', false],
            'tainted' => ['"be7d5bb2f29494c0a1c95c81e8ae8b99-gzip"', '"be7d5bb2f29494c0a1c95c81e8ae8b99"', true],
        ];
    }

    /**
     * @dataProvider getTaintedHeaders
     * @covers ::fixIfNoneMatchHeader
     */
    public function testCanFixATaintedInNoneMatchHeader(string $incoming, string $real, bool $willFix): void
    {
        $requestHeaders = $this->createMock(HeaderBag::class);
        $requestHeaders
            ->expects($this->once())
            ->method('get')
            ->with('if-none-match', false)
            ->willReturn($incoming);

        if ($willFix) {
            $requestHeaders
                ->expects($this->once())
                ->method('set')
                ->with('if-none-match', $real);
        } else {
            $requestHeaders
                ->expects($this->never())
                ->method('set');
        }

        $request = $this->createMock(Request::class);
        $request->headers = $requestHeaders;

        $event = $this->createMock(EventInterface::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $this->listener->fixIfNoneMatchHeader($event);
    }

    public function getRoutesForETags(): array
    {
        return [
            'index route has no ETag' => ['index', false],
            'stats route has no ETag' => ['stats', false],
            'status route has no ETag' => ['status', false],
            'user route has ETag' => ['user', true, true, '{"user":"christer"}'],
            'images route has ETag' => ['images', true, true, '{"search":{"hits":0,"page":1,"limit":20,"count":0},"images":[]}'],
            'image route has ETag' => ['image', true, true, file_get_contents(FIXTURES_DIR . '/image.png')],
            'metadata route has ETag' => ['metadata', true, true, '{"foo":"bar"}'],
            'shorturl route has ETag' => ['globalshorturl', true, true, file_get_contents(FIXTURES_DIR . '/image.png')],
            'response codes other than 200 does not get ETags' => ['globalshorturl', true, false],
        ];
    }

    /**
     * @dataProvider getRoutesForETags
     * @covers ::setETag
     */
    public function testWillSetETagForSomeRoutes(string $route, bool $hasETag, bool $isOk = false, string $content = null): void
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getRoute')
            ->willReturn($route);

        $response = $this->createMock(Response::class);

        if ($hasETag) {
            $response
                ->expects($this->once())
                ->method('isOk')
                ->willReturn($isOk);

            if ($isOk) {
                $response
                    ->expects($this->once())
                    ->method('getContent')
                    ->willReturn($content);

                $response
                    ->expects($this->once())
                    ->method('setETag')
                    ->with('"' . md5($content) . '"');
            }
        } else {
            $response
                ->expects($this->never())
                ->method('isOk');
        }

        $event = $this->createMock(EventInterface::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $event
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn($response);

        $this->listener->setETag($event);
    }
}
