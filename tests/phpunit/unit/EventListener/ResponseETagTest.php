<?php
namespace ImboUnitTest\EventListener;

use Imbo\EventListener\ResponseETag;

/**
 * @coversDefaultClass Imbo\EventListener\ResponseETag
 */
class ResponseETagTest extends ListenerTests {
    /**
     * @var ResponseETag
     */
    private $listener;

    public function setUp() : void {
        $this->listener = new ResponseETag();
    }

    protected function getListener() : ResponseETag {
        return $this->listener;
    }

    public function getTaintedHeaders() : array {
        return [
            'non-tainted' => ['"be7d5bb2f29494c0a1c95c81e8ae8b99"', '"be7d5bb2f29494c0a1c95c81e8ae8b99"', false],
            'tainted' => ['"be7d5bb2f29494c0a1c95c81e8ae8b99-gzip"', '"be7d5bb2f29494c0a1c95c81e8ae8b99"', true],
        ];
    }

    /**
     * @dataProvider getTaintedHeaders
     */
    public function testCanFixATaintedInNoneMatchHeader(string $incoming, string $real, bool $willFix) : void {
        $requestHeaders = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
        $requestHeaders->expects($this->once())->method('get')->with('if-none-match', false)->will($this->returnValue($incoming));

        if ($willFix) {
            $requestHeaders->expects($this->once())->method('set')->with('if-none-match', $real);
        } else {
            $requestHeaders->expects($this->never())->method('set');
        }

        $request = $this->createMock('Imbo\Http\Request\Request');
        $request->headers = $requestHeaders;

        $event = $this->createMock('Imbo\EventManager\Event');
        $event->expects($this->once())->method('getRequest')->will($this->returnValue($request));

        $this->listener->fixIfNoneMatchHeader($event);
    }

    public function getRoutesForETags() : array {
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
     */
    public function testWillSetETagForSomeRoutes(string $route, bool $hasETag, bool $isOk = false, string $content = null) : void {
        $request = $this->createMock('Imbo\Http\Request\Request');
        $request->expects($this->once())->method('getRoute')->will($this->returnValue($route));
        $response = $this->createMock('Imbo\Http\Response\Response');

        if ($hasETag) {
            $response->expects($this->once())->method('isOk')->will($this->returnValue($isOk));

            if ($isOk) {
                $response->expects($this->once())->method('getContent')->will($this->returnValue($content));
                $response->expects($this->once())->method('setETag')->with('"' . md5($content) . '"');
            }
        } else {
            $response->expects($this->never())->method('isOk');
        }

        $event = $this->createMock('Imbo\EventManager\Event');
        $event->expects($this->once())->method('getRequest')->will($this->returnValue($request));
        $event->expects($this->once())->method('getResponse')->will($this->returnValue($response));

        $this->listener->setETag($event);
    }
}
