<?php declare(strict_types=1);
namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\HeaderBag;

/**
 * @coversDefaultClass Imbo\EventListener\ResponseETag
 */
class ResponseETagTest extends ListenerTests
{
    private ResponseETag $listener;

    public function setUp(): void
    {
        $this->listener = new ResponseETag();
    }

    protected function getListener(): ResponseETag
    {
        return $this->listener;
    }

    /**
     * @dataProvider getTaintedHeaders
     * @covers ::fixIfNoneMatchHeader
     */
    public function testCanFixATaintedInNoneMatchHeader(string $incoming, string $real, bool $willFix): void
    {
        /** @var HeaderBag&MockObject */
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

        /** @var EventInterface&MockObject */
        $event = $this->createMock(EventInterface::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $this->listener->fixIfNoneMatchHeader($event);
    }

    /**
     * @dataProvider getRoutesForETags
     * @covers ::setETag
     */
    public function testWillSetETagForSomeRoutes(string $route, bool $hasETag, bool $isOk = false, string $content = null): void
    {
        /** @var Request&MockObject */
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getRoute')
            ->willReturn($route);

        /** @var Response&MockObject */
        $response = $this->createMock(Response::class);

        if ($hasETag) {
            $response
                ->expects($this->once())
                ->method('isOk')
                ->willReturn($isOk);

            if ($isOk && null !== $content) {
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

        /** @var EventInterface&MockObject */
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

    /**
     * @return array<array{incoming:string,real:string,willFix:bool}>
     */
    public static function getTaintedHeaders(): array
    {
        return [
            'non-tainted' => [
                'incoming' => '"be7d5bb2f29494c0a1c95c81e8ae8b99"',
                'real' => '"be7d5bb2f29494c0a1c95c81e8ae8b99"',
                'willFix' => false,
            ],
            'tainted' => [
                'incoming' => '"be7d5bb2f29494c0a1c95c81e8ae8b99-gzip"',
                'real' => '"be7d5bb2f29494c0a1c95c81e8ae8b99"',
                'willFix' => true,
            ],
        ];
    }

    /**
     * @return array<array{route:string,hasETag:bool,isOk?:bool,content?:string}>
     */
    public static function getRoutesForETags(): array
    {
        return [
            'index route has no ETag' => [
                'route' => 'index',
                'hasETag' => false,
            ],
            'stats route has no ETag' => [
                'route' => 'stats',
                'hasETag' => false,
            ],
            'status route has no ETag' => [
                'route' => 'status',
                'hasETag' => false,
            ],
            'user route has ETag' => [
                'route' => 'user',
                'hasETag' => true,
                'isOk' => true,
                'content' => '{"user":"christer"}',
            ],
            'images route has ETag' => [
                'route' => 'images',
                'hasETag' => true,
                'isOk' => true,
                'content' => '{"search":{"hits":0,"page":1,"limit":20,"count":0},"images":[]}',
            ],
            'image route has ETag' => [
                'route' => 'image',
                'hasETag' => true,
                'isOk' => true,
                'content' => file_get_contents(FIXTURES_DIR . '/image.png'),
            ],
            'metadata route has ETag' => [
                'route' => 'metadata',
                'hasETag' => true,
                'isOk' => true,
                'content' => '{"foo":"bar"}',
            ],
            'shorturl route has ETag' => [
                'route' => 'globalshorturl',
                'hasETag' => true,
                'isOk' => true,
                'content' => file_get_contents(FIXTURES_DIR . '/image.png'),
            ],
            'response codes other than 200 does not get ETags' => [
                'route' => 'globalshorturl',
                'hasETag' => true,
                'isOk' => false,
            ],
        ];
    }
}
