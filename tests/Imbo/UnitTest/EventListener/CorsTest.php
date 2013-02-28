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
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Test suite\Unit tests
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

        $this->event = $this->getMock('Imbo\EventManager\EventInterface');
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
     * @covers Imbo\EventListener\Cors::__construct
     * @covers Imbo\EventListener\Cors::getDefinition
     */
    public function testReturnsACorrectListenerDefinition() {
        $listener = new Cors(array(
            'allowedMethods' => array(
                'image'    => array('GET', 'PUT'),
                'images'   => array('GET', 'HEAD'),
                'metadata' => array('POST')
            )
        ));

        $definition = $listener->getDefinition();
        $this->assertCount(8, $definition);
        $events = array();

        foreach ($definition as $d) {
            $events[] = $d->getEventName();
        }

        $this->assertEquals(array(
            'image.get', 'image.put', 'image.options',
            'images.get', 'images.head', 'images.options',
            'metadata.post', 'metadata.options'
        ), $events);
    }

    /**
     * @covers Imbo\EventListener\Cors::options
     * @covers Imbo\EventListener\Cors::originIsAllowed
     */
    public function testDoesNotAddHeadersWhenOriginIsDisallowedAndHttpMethodIsOptions() {
        $this->event->expects($this->never())->method('getResponse');
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
            'Access-Control-Expose-Headers' => 'X-Imbo-Error-Internalcode',
        ));

        $this->response->headers = $headers;
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
            'Access-Control-Expose-Headers' => 'X-Imbo-Error-Internalcode',
        ));

        $this->response->headers = $headers;
        $listener->invoke($this->event);
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

        $this->request->expects($this->once())->method('getResource')->will($this->returnValue('image'));

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
}
