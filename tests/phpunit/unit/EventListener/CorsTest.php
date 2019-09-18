<?php declare(strict_types=1);
namespace ImboUnitTest\EventListener;

use Imbo\EventListener\Cors;

/**
 * @coversDefaultClass Imbo\EventListener\Cors
 */
class CorsTest extends ListenerTests {
    /**
     * @var Cors
     */
    private $listener;

    private $event;
    private $request;
    private $response;

    public function setUp() : void {
        $requestHeaders = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
        $requestHeaders->expects($this->any())->method('get')->with('Origin')->will($this->returnValue('http://imbo-project.org'));

        $this->request = $this->createMock('Imbo\Http\Request\Request');
        $this->request->headers = $requestHeaders;

        $this->response = $this->createMock('Imbo\Http\Response\Response');
        $this->response->headers = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');

        $this->event = $this->createMock('Imbo\EventManager\Event');
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));

        $this->listener = new Cors();
    }

    protected function getListener() : Cors {
        return $this->listener;
    }

    /**
     * @covers ::__construct
     * @covers ::getAllowedOrigins
     */
    public function testCleansUpOrigins() : void {
        $listener = new Cors([
            'allowedOrigins' => [
                'HTTP://www.rexxars.com:8080/',
                'https://IMBO-project.org',
            ]
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
    public function testDoesNotAddHeadersWhenOriginIsDisallowedAndHttpMethodIsOptions() : void {
        $headers = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
        $headers->expects($this->never())->method('add');
        $this->response->headers = $headers;

        $this->listener->options($this->event);
    }

    /**
     * @covers ::invoke
     * @covers ::originIsAllowed
     */
    public function testDoesNotAddHeadersWhenOriginIsDisallowedAndHttpMethodIsOtherThanOptions() : void {
        $this->event->expects($this->never())->method('getResponse');
        $this->listener->invoke($this->event);
    }

    /**
     * @covers ::invoke
     * @covers ::originIsAllowed
     */
    public function testAddsHeadersIfWildcardOriginIsDefined() : void {
        $listener = new Cors([
            'allowedOrigins' => ['*']
        ]);

        $headers = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
        $headers->expects($this->once())->method('add')->with([
            'Access-Control-Allow-Origin' => 'http://imbo-project.org',
        ]);

        $this->response->headers = $headers;
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $route = $this->createMock('Imbo\Router\Route');
        $route->expects($this->once())->method('__toString')->will($this->returnValue('index'));
        $this->request->expects($this->once())->method('getRoute')->will($this->returnValue($route));
        $listener->invoke($this->event);
    }

    /**
     * @covers ::invoke
     * @covers ::originIsAllowed
     */
    public function testAddsHeadersIfOriginIsDefinedAndAllowed() : void {
        $listener = new Cors([
            'allowedOrigins' => ['http://imbo-project.org']
        ]);

        $headers = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');

        $headers->expects($this->once())->method('add')->with([
            'Access-Control-Allow-Origin' => 'http://imbo-project.org',
        ]);

        $this->response->headers = $headers;
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $route = $this->createMock('Imbo\Router\Route');
        $route->expects($this->once())->method('__toString')->will($this->returnValue('index'));
        $this->request->expects($this->once())->method('getRoute')->will($this->returnValue($route));
        $listener->invoke($this->event);
    }

    /**
     * @covers ::invoke
     * @covers ::setExposedHeaders
     */
    public function testIncludesAllImboHeadersAsExposedHeaders() : void {
        $listener = new Cors([
            'allowedOrigins' => ['http://imbo-project.org']
        ]);

        $headerIterator = new \ArrayIterator(['x-imbo-something' => 'value', 'not-included' => 'foo']);

        $headers = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
        $headers->expects($this->once())->method('getIterator')->will($this->returnValue($headerIterator));
        $headers->expects($this->at(0))->method('add')->with([
            'Access-Control-Allow-Origin' => 'http://imbo-project.org',
        ]);
        $headers->expects($this->at(2))->method('add')->with([
            'Access-Control-Expose-Headers' => 'X-Imbo-ImageIdentifier, X-Imbo-Something',
        ]);

        $this->response->headers = $headers;
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $route = $this->createMock('Imbo\Router\Route');
        $route->expects($this->once())->method('__toString')->will($this->returnValue('index'));
        $this->request->expects($this->once())->method('getRoute')->will($this->returnValue($route));
        $listener->invoke($this->event);
        $listener->setExposedHeaders($this->event);
    }

    /**
     * @covers ::setExposedHeaders
     */
    public function testDoesNotAddExposeHeadersHeaderWhenOriginIsInvalid() : void {
        $listener = new Cors([]);

        $headers = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
        $headers->expects($this->never())->method('add');

        $this->response->headers = $headers;
        $listener->setExposedHeaders($this->event);
    }

    /**
     * @covers ::options
     */
    public function testSetsCorrectResposeHeadersOnOptionsRequestWhenOriginIsAllowed() : void {
        $listener = new Cors([
            'allowedOrigins' => ['*'],
            'allowedMethods' => [
                'image' => ['HEAD'],
            ],
            'maxAge' => 60,
        ]);

        $route = $this->createMock('Imbo\Router\Route');
        $route->expects($this->once())->method('__toString')->will($this->returnValue('image'));
        $this->request->expects($this->once())->method('getRoute')->will($this->returnValue($route));

        $this->request->headers = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
        $this->request->headers
            ->expects($this->at(0))
            ->method('get')
            ->with('Origin')
            ->will($this->returnValue('http://imbo-project.org'));

        $this->request->headers
            ->expects($this->at(1))
            ->method('get')
            ->with('Access-Control-Request-Headers', '')
            ->will($this->returnValue('x-imbo-signature,something-else'));

        $headers = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
        $headers->expects($this->once())->method('add')->with([
            'Access-Control-Allow-Origin' => 'http://imbo-project.org',
            'Access-Control-Allow-Methods' => 'OPTIONS, HEAD',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept, X-Imbo-Signature',
            'Access-Control-Max-Age' => 60,
        ]);

        $this->response->headers = $headers;
        $this->response->expects($this->once())->method('setStatusCode')->with(204);
        $this->event->expects($this->once())->method('stopPropagation');
        $listener->options($this->event);
    }

    /**
     * @covers ::getSubscribedEvents
     */
    public function testReturnsSubscribedEvents() : void {
        $className = get_class($this->listener);
        $this->assertIsArray($className::getSubscribedEvents());
    }

    /**
     * @covers ::invoke
     */
    public function testDoesNotAddAccessControlHeadersWhenOriginIsNotAllowed() : void {
        $route = $this->createMock('Imbo\Router\Route');
        $route->expects($this->once())->method('__toString')->will($this->returnValue('image'));

        $requestHeaders = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
        $requestHeaders->expects($this->any())->method('get')->with('Origin')->will($this->returnValue('http://somehost'));

        $request = $this->createMock('Imbo\Http\Request\Request');
        $request->expects($this->once())->method('getRoute')->will($this->returnValue($route));
        $request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $request->headers = $requestHeaders;

        $event = $this->createMock('Imbo\EventManager\Event');
        $event->expects($this->once())->method('getRequest')->will($this->returnValue($request));
        $event->expects($this->once())->method('getResponse')->will($this->returnValue($this->response));

        $listener = new Cors([
            'allowedOrigin' => 'http://imbo',
        ]);
        $listener->invoke($event);
    }

    public function getAllowedMethodsParams() : array {
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
    public function testWillSubscribeToTheCorrectEventsBasedOnParams(array $params, array $events) : void {
        $listener = new Cors($params);

        $headers = $this->createMock('Symfony\Component\HttpFoundation\ResponseHeaderBag');
        $headers->expects($this->once())->method('set')->with('Allow', 'OPTIONS', false);

        $response = $this->createMock('Imbo\Http\Response\Response');
        $response->headers = $headers;

        $manager = $this->createMock('Imbo\EventManager\EventManager');
        $manager->expects($this->once())->method('addCallbacks')->with('handler', $events);

        $event = $this->createMock('Imbo\EventManager\EventInterface');
        $event->expects($this->once())->method('getManager')->will($this->returnValue($manager));
        $event->expects($this->once())->method('getHandler')->will($this->returnValue('handler'));
        $event->expects($this->once())->method('getResponse')->will($this->returnValue($response));

        $listener->subscribe($event);
    }

    /**
     * @covers ::invoke
     */
    public function testAddsVaryHeaderContainingOriginRegardlessOfAllowedStatus() : void {
        $this->request->expects($this->any())->method('getMethod')->will($this->returnValue('GET'));
        $route = $this->createMock('Imbo\Router\Route');
        $route->expects($this->any())->method('__toString')->will($this->returnValue('index'));
        $this->request->expects($this->any())->method('getRoute')->will($this->returnValue($route));

        // Allowed
        $listener = new Cors([
            'allowedOrigins' => ['http://imbo-project.org']
        ]);

        $event = $this->createMock('Imbo\EventManager\Event');
        $event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));

        $response = $this->createMock('Imbo\Http\Response\Response');
        $response->headers = $this->createMock('Symfony\Component\HttpFoundation\HeaderBag');
        $response->expects($this->once())->method('setVary')->with('Origin', false);
        $event->expects($this->any())->method('getResponse')->will($this->returnValue($response));

        $listener->invoke($event);

        // Disallowed
        $listener = new Cors([
            'allowedOrigins' => []
        ]);

        $event = $this->createMock('Imbo\EventManager\Event');
        $event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));

        $response = $this->createMock('Imbo\Http\Response\Response');
        $response->expects($this->once())->method('setVary')->with('Origin', false);
        $event->expects($this->any())->method('getResponse')->will($this->returnValue($response));

        $listener->invoke($event);
    }
}
