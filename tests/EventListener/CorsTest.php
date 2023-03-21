<?php declare(strict_types=1);
namespace Imbo\EventListener;

use ArrayIterator;
use Imbo\EventManager\EventInterface;
use Imbo\EventManager\EventManager;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use Imbo\Router\Route;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * @coversDefaultClass Imbo\EventListener\Cors
 */
class CorsTest extends ListenerTests
{
    private $listener;
    private $event;
    private $request;
    private $response;

    public function setUp(): void
    {
        $requestHeaders = $this->createMock(HeaderBag::class);
        $requestHeaders
            ->expects($this->any())
            ->method('get')
            ->with('Origin')
            ->willReturn('http://imbo-project.org');

        $this->request = $this->createMock(Request::class);
        $this->request->headers = $requestHeaders;

        $this->response = $this->createMock(Response::class);
        $this->response->headers = $this->createMock(ResponseHeaderBag::class);

        $this->event = $this->createConfiguredMock(EventInterface::class, [
            'getResponse' => $this->response,
            'getRequest' => $this->request,
        ]);

        $this->listener = new Cors();
    }

    protected function getListener(): Cors
    {
        return $this->listener;
    }

    /**
     * @covers ::__construct
     * @covers ::getAllowedOrigins
     */
    public function testCleansUpOrigins(): void
    {
        $listener = new Cors([
            'allowedOrigins' => [
                'HTTP://www.rexxars.com:8080/',
                'https://IMBO-project.org',
            ],
        ]);

        $allowed = $listener->getAllowedOrigins();

        $expected = [
            'http://www.rexxars.com:8080',
            'https://imbo-project.org',
        ];

        foreach ($expected as $e) {
            $this->assertContains($e, $allowed);
        }

        $this->assertCount(2, $allowed);
    }

    /**
     * @covers ::options
     * @covers ::originIsAllowed
     */
    public function testDoesNotAddHeadersWhenOriginIsDisallowedAndHttpMethodIsOptions(): void
    {
        $headers = $this->createMock(ResponseHeaderBag::class);
        $headers
            ->expects($this->never())
            ->method('add');
        $this->response->headers = $headers;

        $this->listener->options($this->event);
    }

    /**
     * @covers ::invoke
     * @covers ::originIsAllowed
     */
    public function testDoesNotAddHeadersWhenOriginIsDisallowedAndHttpMethodIsOtherThanOptions(): void
    {
        $this->event
            ->expects($this->never())
            ->method('getResponse');
        $this->listener->invoke($this->event);
    }

    /**
     * @covers ::invoke
     * @covers ::originIsAllowed
     */
    public function testAddsHeadersIfWildcardOriginIsDefined(): void
    {
        $listener = new Cors([
            'allowedOrigins' => ['*'],
        ]);

        $headers = $this->createMock(ResponseHeaderBag::class);
        $headers
            ->expects($this->once())
            ->method('add')
            ->with([
                'Access-Control-Allow-Origin' => 'http://imbo-project.org',
            ]);

        $this->response->headers = $headers;
        $this->request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');
        $route = $this->createConfiguredMock(Route::class, [
            '__toString' => 'index',
        ]);
        $this->request
            ->expects($this->once())
            ->method('getRoute')
            ->willReturn($route);
        $listener->invoke($this->event);
    }

    /**
     * @covers ::invoke
     * @covers ::originIsAllowed
     */
    public function testAddsHeadersIfOriginIsDefinedAndAllowed(): void
    {
        $listener = new Cors([
            'allowedOrigins' => ['http://imbo-project.org'],
        ]);

        $headers = $this->createMock(ResponseHeaderBag::class);
        $headers
            ->expects($this->once())
            ->method('add')
            ->with([
                'Access-Control-Allow-Origin' => 'http://imbo-project.org',
            ]);

        $this->response->headers = $headers;
        $this->request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');
        $route = $this->createConfiguredMock(Route::class, [
            '__toString' => 'index',
        ]);
        $this->request
            ->expects($this->once())
            ->method('getRoute')
            ->willReturn($route);
        $listener->invoke($this->event);
    }

    /**
     * @covers ::invoke
     * @covers ::setExposedHeaders
     */
    public function testIncludesAllImboHeadersAsExposedHeaders(): void
    {
        $listener = new Cors([
            'allowedOrigins' => ['http://imbo-project.org'],
        ]);

        $headerIterator = new ArrayIterator([
            'x-imbo-something' => 'value',
            'not-included' => 'foo',
        ]);

        $headers = $this->createConfiguredMock(ResponseHeaderBag::class, [
            'getIterator' => $headerIterator,
        ]);
        $headers
            ->method('add')
            ->with($this->callback(
                static function (array $headers): bool {
                    static $i = 0;
                    return match ([$i++, $headers]) {
                        [0, ['Access-Control-Allow-Origin' => 'http://imbo-project.org']],
                        [1, ['Access-Control-Expose-Headers' => 'X-Imbo-ImageIdentifier, X-Imbo-Something']] => true,
                        default => false,
                    };
                },
            ));

        $this->response->headers = $headers;

        $route = $this->createConfiguredMock(Route::class, [
            '__toString' => 'index',
        ]);

        $this->request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');
        $this->request
            ->expects($this->once())
            ->method('getRoute')
            ->willReturn($route);

        $listener->invoke($this->event);
        $listener->setExposedHeaders($this->event);
    }

    /**
     * @covers ::setExposedHeaders
     */
    public function testDoesNotAddExposeHeadersHeaderWhenOriginIsInvalid(): void
    {
        $listener = new Cors([]);

        $headers = $this->createMock(ResponseHeaderBag::class);
        $headers
            ->expects($this->never())
            ->method('add');

        $this->response->headers = $headers;
        $listener->setExposedHeaders($this->event);
    }

    /**
     * @covers ::options
     */
    public function testSetsCorrectResposeHeadersOnOptionsRequestWhenOriginIsAllowed(): void
    {
        $listener = new Cors([
            'allowedOrigins' => ['*'],
            'allowedMethods' => [
                'image' => ['HEAD'],
            ],
            'maxAge' => 60,
        ]);

        $route = $this->createConfiguredMock(Route::class, [
            '__toString' => 'image',
        ]);

        $this->request
            ->expects($this->once())
            ->method('getRoute')
            ->willReturn($route);

        $this->request->headers = $this->createMock(HeaderBag::class);
        $this->request->headers
            ->method('get')
            ->willReturnCallback(
                static function (string $header, ?string $value = ''): string {
                    static $i = 0;
                    return match ([$i++, $header, $value]) {
                        [0, 'Origin', null] => 'http://imbo-project.org',
                        [1, 'Access-Control-Request-Headers', ''] => 'x-imbo-signature,something-else',
                    };
                },
            );

        $headers = $this->createMock(ResponseHeaderBag::class);
        $headers
            ->expects($this->once())
            ->method('add')
            ->with([
                'Access-Control-Allow-Origin'  => 'http://imbo-project.org',
                'Access-Control-Allow-Methods' => 'OPTIONS, HEAD',
                'Access-Control-Allow-Headers' => 'Content-Type, Accept, X-Imbo-Signature',
                'Access-Control-Max-Age'       => 60,
            ]);

        $this->response->headers = $headers;
        $this->response
            ->expects($this->once())
            ->method('setStatusCode')
            ->with(Response::HTTP_NO_CONTENT);
        $this->event
            ->expects($this->once())
            ->method('stopPropagation');

        $listener->options($this->event);
    }

    /**
     * @covers ::getSubscribedEvents
     */
    public function testReturnsSubscribedEvents(): void
    {
        $className = get_class($this->listener);
        $this->assertIsArray($className::getSubscribedEvents());
    }

    /**
     * @covers ::invoke
     */
    public function testDoesNotAddAccessControlHeadersWhenOriginIsNotAllowed(): void
    {
        $route = $this->createConfiguredMock(Route::class, [
            '__toString' => 'image',
        ]);

        $requestHeaders = $this->createMock(HeaderBag::class);
        $requestHeaders
            ->expects($this->any())
            ->method('get')
            ->with('Origin')
            ->willReturn('http://somehost');

        $request = $this->createConfiguredMock(Request::class, [
            'getRoute' => $route,
            'getMethod' => 'GET',
        ]);

        $request->headers = $requestHeaders;

        $event = $this->createConfiguredMock(EventInterface::class, [
            'getRequest' => $request,
            'getResponse' => $this->response,
        ]);

        $listener = new Cors([
            'allowedOrigin' => 'http://imbo',
        ]);

        $responseHeaders = $this->createMock(ResponseHeaderBag::class);
        $responseHeaders
            ->expects($this->never())
            ->method('add');
        $this->response->headers = $responseHeaders;

        $listener->invoke($event);
    }

    public static function getAllowedMethodsParams(): array
    {
        return [
            'default' => [
                'params' => [],
                'events' => [
                    'index.get' => ['invoke' => 1000],
                    'index.head' => ['invoke' => 1000],
                    'index.options' => ['options' => 20],

                    'image.get' => ['invoke' => 1000],
                    'image.head' => ['invoke' => 1000],
                    'image.options' => ['options' => 20],

                    'images.get' => ['invoke' => 1000],
                    'images.head' => ['invoke' => 1000],
                    'images.options' => ['options' => 20],

                    'globalimages.get' => ['invoke' => 1000],
                    'globalimages.head' => ['invoke' => 1000],
                    'globalimages.options' => ['options' => 20],

                    'metadata.get' => ['invoke' => 1000],
                    'metadata.head' => ['invoke' => 1000],
                    'metadata.options' => ['options' => 20],

                    'status.get' => ['invoke' => 1000],
                    'status.head' => ['invoke' => 1000],
                    'status.options' => ['options' => 20],

                    'stats.get' => ['invoke' => 1000],
                    'stats.head' => ['invoke' => 1000],
                    'stats.options' => ['options' => 20],

                    'user.get' => ['invoke' => 1000],
                    'user.head' => ['invoke' => 1000],
                    'user.options' => ['options' => 20],

                    'globalshorturl.get' => ['invoke' => 1000],
                    'globalshorturl.head' => ['invoke' => 1000],
                    'globalshorturl.options' => ['options' => 20],

                    'shorturl.get' => ['invoke' => 1000],
                    'shorturl.head' => ['invoke' => 1000],
                    'shorturl.options' => ['options' => 20],

                    'shorturls.get' => ['invoke' => 1000],
                    'shorturls.head' => ['invoke' => 1000],
                    'shorturls.options' => ['options' => 20],
                ],
            ],
            'some endpoints' => [
                'params' => [
                    'allowedMethods' => [
                        'stats' => ['GET'],
                        'images' => ['POST'],
                    ],
                ],
                'events' => [
                    'stats.get' => ['invoke' => 1000],
                    'stats.options' => ['options' => 20],
                    'images.post' => ['invoke' => 1000],
                    'images.options' => ['options' => 20],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getAllowedMethodsParams
     * @covers ::subscribe
     */
    public function testWillSubscribeToTheCorrectEventsBasedOnParams(array $params, array $events): void
    {
        $listener = new Cors($params);

        $headers = $this->createMock(ResponseHeaderBag::class);
        $headers
            ->expects($this->once())
            ->method('set')
            ->with('Allow', 'OPTIONS', false);

        $response = $this->createMock(Response::class);
        $response->headers = $headers;

        $manager = $this->createMock(EventManager::class);
        $manager
            ->expects($this->once())
            ->method('addCallbacks')
            ->with('handler', $events);

        $event = $this->createConfiguredMock(EventInterface::class, [
            'getManager' => $manager,
            'getHandler' => 'handler',
            'getResponse' => $response,
        ]);

        $listener->subscribe($event);
    }

    /**
     * @covers ::invoke
     */
    public function testAddsVaryHeaderContainingOriginRegardlessOfAllowedStatus(): void
    {
        $this->request
            ->expects($this->any())
            ->method('getMethod')
            ->willReturn('GET');
        $route = $this->createConfiguredMock(Route::class, [
            '__toString' => 'index',
        ]);
        $this->request
            ->expects($this->any())
            ->method('getRoute')
            ->willReturn($route);

        // Allowed
        $listener = new Cors([
            'allowedOrigins' => ['http://imbo-project.org'],
        ]);

        $response = $this->createMock(Response::class);
        $response->headers = $this->createMock(ResponseHeaderBag::class);
        $response
            ->expects($this->once())
            ->method('setVary')
            ->with('Origin', false);

        $event = $this->createConfiguredMock(EventInterface::class, [
            'getRequest' => $this->request,
            'getResponse' => $response,
        ]);

        $listener->invoke($event);

        // Disallowed
        $listener = new Cors([
            'allowedOrigins' => [],
        ]);

        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('setVary')
            ->with('Origin', false);

        $event = $this->createConfiguredMock(EventInterface::class, [
            'getRequest' => $this->request,
            'getResponse' => $response,
        ]);

        $listener->invoke($event);
    }
}
