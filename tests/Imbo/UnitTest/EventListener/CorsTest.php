<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\EventListener;

use Imbo\EventListener\Cors;

/**
 * @covers Imbo\EventListener\Cors
 * @group unit
 */
class CorsTest extends ListenerTests {
    /**
     * @var Cors
     */
    private $listener;

    private $event;
    private $request;
    private $response;

    /**
     * Set up the listener
     *
     * @covers Imbo\EventListener\Cors::__construct
     */
    public function setUp() {
        $requestHeaders = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');
        $requestHeaders->expects($this->any())->method('get')->with('Origin', '*')->will($this->returnValue('http://imbo-project.org'));

        $this->request = $this->getMock('Imbo\Http\Request\Request');
        $this->request->headers = $requestHeaders;

        $this->response = $this->getMock('Imbo\Http\Response\Response');

        $this->event = $this->getMock('Imbo\EventManager\Event');
        $this->event->expects($this->any())->method('getResponse')->will($this->returnValue($this->response));
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));

        $this->listener = new Cors();
    }

    /**
     * Tear down the listener
     */
    public function tearDown() {
        $this->request = null;
        $this->response = null;
        $this->event = null;
        $this->listener = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getListener() {
        return $this->listener;
    }

    /**
     * @covers Imbo\EventListener\Cors::__construct
     * @covers Imbo\EventListener\Cors::getAllowedOrigins
     */
    public function testCleansUpOrigins() {
        $listener = new Cors(array(
            'allowedOrigins' => array(
                'HTTP://www.rexxars.com:8080/',
                'https://IMBO-project.org',
            )
        ));

        $allowed = $listener->getAllowedOrigins();

        $expected = array(
            'http://www.rexxars.com:8080',
            'https://imbo-project.org',
        );

        foreach ($expected as $e) {
            $this->assertContains($e, $allowed);
        }

        $this->assertCount(2, $allowed);
    }

    /**
     * @covers Imbo\EventListener\Cors::options
     * @covers Imbo\EventListener\Cors::originIsAllowed
     */
    public function testDoesNotAddHeadersWhenOriginIsDisallowedAndHttpMethodIsOptions() {
        $headers = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');
        $headers->expects($this->never())->method('add');
        $this->response->headers = $headers;

        $this->listener->options($this->event);
    }

    /**
     * @covers Imbo\EventListener\Cors::invoke
     * @covers Imbo\EventListener\Cors::originIsAllowed
     */
    public function testDoesNotAddHeadersWhenOriginIsDisallowedAndHttpMethodIsOtherThanOptions() {
        $this->event->expects($this->never())->method('getResponse');
        $this->listener->invoke($this->event);
    }

    /**
     * @covers Imbo\EventListener\Cors::invoke
     * @covers Imbo\EventListener\Cors::originIsAllowed
     */
    public function testAddsHeadersIfWildcardOriginIsDefined() {
        $listener = new Cors(array(
            'allowedOrigins' => array('*')
        ));

        $headers = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');
        $headers->expects($this->once())->method('add')->with(array(
            'Access-Control-Allow-Origin' => 'http://imbo-project.org',
        ));

        $this->response->headers = $headers;
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $route = $this->getMock('Imbo\Router\Route');
        $route->expects($this->once())->method('__toString')->will($this->returnValue('index'));
        $this->request->expects($this->once())->method('getRoute')->will($this->returnValue($route));
        $listener->invoke($this->event);
    }

    /**
     * @covers Imbo\EventListener\Cors::invoke
     * @covers Imbo\EventListener\Cors::originIsAllowed
     */
    public function testAddsHeadersIfOriginIsDefinedAndAllowed() {
        $listener = new Cors(array(
            'allowedOrigins' => array('http://imbo-project.org')
        ));

        $headers = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');
        $headers->expects($this->once())->method('add')->with(array(
            'Access-Control-Allow-Origin' => 'http://imbo-project.org',
        ));

        $this->response->headers = $headers;
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $route = $this->getMock('Imbo\Router\Route');
        $route->expects($this->once())->method('__toString')->will($this->returnValue('index'));
        $this->request->expects($this->once())->method('getRoute')->will($this->returnValue($route));
        $listener->invoke($this->event);
    }

    /**
     * @covers Imbo\EventListener\Cors::invoke
     * @covers Imbo\EventListener\Cors::setExposedHeaders
     */
    public function testIncludesAllImboHeadersAsExposedHeaders() {
        $listener = new Cors(array(
            'allowedOrigins' => array('http://imbo-project.org')
        ));

        $headerIterator = new \ArrayIterator(array('x-imbo-something' => 'value', 'not-included' => 'foo'));

        $headers = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');
        $headers->expects($this->once())->method('getIterator')->will($this->returnValue($headerIterator));
        $headers->expects($this->at(0))->method('add')->with(array(
            'Access-Control-Allow-Origin' => 'http://imbo-project.org',
        ));
        $headers->expects($this->at(2))->method('add')->with(array(
            'Access-Control-Expose-Headers' => 'X-Imbo-ImageIdentifier, X-Imbo-Something',
        ));

        $this->response->headers = $headers;
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $route = $this->getMock('Imbo\Router\Route');
        $route->expects($this->once())->method('__toString')->will($this->returnValue('index'));
        $this->request->expects($this->once())->method('getRoute')->will($this->returnValue($route));
        $listener->invoke($this->event);
        $listener->setExposedHeaders($this->event);
    }

    /**
     * @covers Imbo\EventListener\Cors::setExposedHeaders
     */
    public function testDoesNotAddExposeHeadersHeaderWhenOriginIsInvalid() {
        $listener = new Cors(array());

        $headers = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');
        $headers->expects($this->never())->method('add');

        $this->response->headers = $headers;
        $listener->setExposedHeaders($this->event);
    }

    /**
     * @covers Imbo\EventListener\Cors::options
     */
    public function testSetsCorrectResposeHeadersOnOptionsRequestWhenOriginIsAllowed() {
        $listener = new Cors(array(
            'allowedOrigins' => array('*'),
            'allowedMethods' => array(
                'image' => array('HEAD'),
            ),
            'maxAge' => 60,
        ));

        $route = $this->getMock('Imbo\Router\Route');
        $route->expects($this->once())->method('__toString')->will($this->returnValue('image'));
        $this->request->expects($this->once())->method('getRoute')->will($this->returnValue($route));

        $headers = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');
        $headers->expects($this->once())->method('add')->with(array(
            'Access-Control-Allow-Origin' => 'http://imbo-project.org',
            'Access-Control-Allow-Methods' => 'OPTIONS, HEAD',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept',
            'Access-Control-Max-Age' => 60,
        ));

        $this->response->headers = $headers;
        $this->response->expects($this->once())->method('setStatusCode')->with(204);
        $this->event->expects($this->once())->method('stopPropagation');
        $listener->options($this->event);
    }

    /**
     * @covers Imbo\EventListener\Cors::getSubscribedEvents
     */
    public function testReturnsSubscribedEvents() {
        $className = get_class($this->listener);
        $this->assertInternalType('array', $className::getSubscribedEvents());
    }

    /**
     * @covers Imbo\EventListener\Cors::invoke
     */
    public function testDoesNotAddAccessControlHeadersWhenOriginIsNotAllowed() {
        $route = $this->getMock('Imbo\Router\Route');
        $route->expects($this->once())->method('__toString')->will($this->returnValue('image'));

        $requestHeaders = $this->getMock('Symfony\Component\HttpFoundation\HeaderBag');
        $requestHeaders->expects($this->any())->method('get')->with('Origin', '*')->will($this->returnValue('http://somehost'));

        $request = $this->getMock('Imbo\Http\Request\Request');
        $request->expects($this->once())->method('getRoute')->will($this->returnValue($route));
        $request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $request->headers = $requestHeaders;

        $event = $this->getMock('Imbo\EventManager\Event');
        $event->expects($this->once())->method('getRequest')->will($this->returnValue($request));
        $event->expects($this->never())->method('getResponse');

        $listener = new Cors(array(
            'allowedOrigin' => 'http://imbo',
        ));
        $listener->invoke($event);
    }

    /**
     * Data provider
     *
     * @return array[]
     */
    public function getAllowedMethodsParams() {
        return array(
            'default' => array(
                'params' => array(),
                'events' => array(
                    'index.get' => array('invoke' => 20),
                    'index.head' => array('invoke' => 20),
                    'index.options' => array('options' => 20),
                    'image.get' => array('invoke' => 20),
                    'image.head' => array('invoke' => 20),
                    'image.options' => array('options' => 20),
                    'images.get' => array('invoke' => 20),
                    'images.head' => array('invoke' => 20),
                    'images.options' => array('options' => 20),
                    'metadata.get' => array('invoke' => 20),
                    'metadata.head' => array('invoke' => 20),
                    'metadata.options' => array('options' => 20),
                    'status.get' => array('invoke' => 20),
                    'status.head' => array('invoke' => 20),
                    'status.options' => array('options' => 20),
                    'stats.get' => array('invoke' => 20),
                    'stats.head' => array('invoke' => 20),
                    'stats.options' => array('options' => 20),
                    'user.get' => array('invoke' => 20),
                    'user.head' => array('invoke' => 20),
                    'user.options' => array('options' => 20),
                    'shorturl.get' => array('invoke' => 20),
                    'shorturl.head' => array('invoke' => 20),
                    'shorturl.options' => array('options' => 20),
                ),
            ),
            'some endpoints' => array(
                'params' => array(
                    'allowedMethods' => array(
                        'stats' => array('GET'),
                        'image' => array('PUT'),
                    ),
                ),
                'events' => array(
                    'stats.get' => array('invoke' => 20),
                    'stats.options' => array('options' => 20),
                    'image.put' => array('invoke' => 20),
                    'image.options' => array('options' => 20),
                ),
            ),
        );
    }

    /**
     * @dataProvider getAllowedMethodsParams
     * @covers Imbo\EventListener\Cors::subscribe
     */
    public function testWillSubscribeToTheCorrectEventsBasedOnParams($params, $events) {
        $listener = new Cors($params);

        $headers = $this->getMock('Symfony\Component\HttpFoundation\ResponseHeaderBag');
        $headers->expects($this->once())->method('set')->with('Allow', 'OPTIONS', false);

        $response = $this->getMock('Imbo\Http\Response\Response');
        $response->headers = $headers;

        $manager = $this->getMock('Imbo\EventManager\EventManager');
        $manager->expects($this->once())->method('addCallbacks')->with('handler', $events);

        $event = $this->getMock('Imbo\EventManager\EventInterface');
        $event->expects($this->once())->method('getManager')->will($this->returnValue($manager));
        $event->expects($this->once())->method('getHandler')->will($this->returnValue('handler'));
        $event->expects($this->once())->method('getResponse')->will($this->returnValue($response));

        $listener->subscribe($event);
    }
}
