<?php
/**
 * Imbo
 *
 * Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * * The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\UnitTest\EventListener;

use Imbo\EventListener\Cors,
    Imbo\Container;

/**
 * @package TestSuite\UnitTests
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\EventListener\Cors
 */
class CorsTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Imbo\EventListener\Cors
     */
    private $listener;

    /**
     * @var Imbo\EventManager\EventInterface
     */
    private $event;

    /**
     * @var Imbo\Container
     */
    private $container;

    /**
     * @var Imbo\Http\Request\RequestInterface
     */
    private $request;

    /**
     * @var Imbo\Http\Response\ResponseInterface
     */
    private $response;

    /**
     * Set up method
     *
     * @covers Imbo\EventListener\Cors::__construct
     */
    public function setUp() {
        $this->request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $this->response = $this->getMock('Imbo\Http\Response\ResponseInterface');
        $this->event = $this->getMock('Imbo\EventManager\EventInterface');
        $requestHeaders = $this->getMock('Imbo\Http\HeaderContainer');

        $this->container = new Container();
        $this->container->request = $this->request;
        $this->container->response= $this->response;

        $requestHeaders->expects($this->any())->method('get')->with('Origin', '*')->will($this->returnValue('http://imbo-project.org'));
        $this->event->expects($this->any())->method('getContainer')->will($this->returnValue($this->container));
        $this->request->expects($this->any())->method('getHeaders')->will($this->returnValue($requestHeaders));

        $this->listener = new Cors();
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->request = null;
        $this->response = null;
        $this->event = null;
        $this->container = null;
        $this->listener = null;
    }

    /**
     * @covers Imbo\EventListener\Cors::__construct
     * @covers Imbo\EventListener\Cors::getAllowedOrigins
     */
    public function testListenerShouldCleanupOrigins() {
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
     * @covers Imbo\EventListener\Cors::getEvents
     */
    public function testGetEventsShouldGenerateCorrectEvents() {
        $listener = new Cors(array(
            'allowedMethods' => array(
                'image'    => array('GET', 'PUT'),
                'images'   => array('GET', 'HEAD'),
                'metadata' => array('POST')
            )
        ));

        $events = $listener->getEvents();
        $expected = array(
            'image.get.pre',
            'image.put.pre',
            'image.options.pre',

            'images.get.pre',
            'images.head.pre',
            'images.options.pre',

            'metadata.post.pre',
            'metadata.options.pre',
        );

        foreach ($expected as $e) {
            $this->assertContains($e, $events);
        }

        $this->assertCount(8, $events);
    }

    /**
     * @covers Imbo\EventListener\Cors::invoke
     * @covers Imbo\EventListener\Cors::originIsAllowed
     */
    public function testListenerShouldNotAddHeadersWhenOriginIsDisallowed() {
        $this->response->expects($this->never())->method('getHeaders');
        $this->listener->invoke($this->event);
    }

    /**
     * @covers Imbo\EventListener\Cors::invoke
     * @covers Imbo\EventListener\Cors::originIsAllowed
     */
    public function testListenerShouldAllowIfWildcardOriginIsDefined() {
        $listener = new Cors(array(
            'allowedOrigins' => array('*')
        ));

        $headers = $this->getMock('Imbo\Http\HeaderContainer');
        $headers->expects($this->at(0))->method('set')->with('Access-Control-Allow-Origin', 'http://imbo-project.org');
        $headers->expects($this->at(1))->method('set')->with('Access-Control-Expose-Headers', 'X-Imbo-Error-Internalcode');

        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($headers));
        $listener->invoke($this->event);
    }

    /**
     * @covers Imbo\EventListener\Cors::invoke
     * @covers Imbo\EventListener\Cors::originIsAllowed
     */
    public function testListenerShouldAllowIfOriginIsDefined() {
        $listener = new Cors(array(
            'allowedOrigins' => array('http://imbo-project.org')
        ));

        $headers = $this->getMock('Imbo\Http\HeaderContainer');
        $headers->expects($this->at(0))->method('set')->with('Access-Control-Allow-Origin', 'http://imbo-project.org');
        $headers->expects($this->at(1))->method('set')->with('Access-Control-Expose-Headers', 'X-Imbo-Error-Internalcode');

        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($headers));
        $listener->invoke($this->event);
    }

    /**
     * @covers Imbo\EventListener\Cors::invoke
     */
    public function testListenerShouldSetAllowedMethodsOnOptionsRequest() {
        $listener = new Cors(array(
            'allowedOrigins' => array('*'),
            'allowedMethods' => array(
                'image' => array('HEAD'),
            ),
        ));

        $this->request->expects($this->once())->method('getResource')->will($this->returnValue('image'));
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('OPTIONS'));

        $headers = $this->getMock('Imbo\Http\HeaderContainer');
        $headers->expects($this->at(1))->method('set')->with('Access-Control-Allow-Methods', 'OPTIONS, HEAD');

        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($headers));
        $listener->invoke($this->event);
    }

    /**
     * @covers Imbo\EventListener\Cors::invoke
     */
    public function testListenerShouldSetDefinedMaxAgeOnOptionsRequest() {
        $listener = new Cors(array(
            'allowedOrigins' => array('*'),
            'maxAge'         => 60,
        ));

        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('OPTIONS'));

        $headers = $this->getMock('Imbo\Http\HeaderContainer');
        $headers->expects($this->at(3))->method('set')->with('Access-Control-Max-Age', 60);

        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($headers));
        $listener->invoke($this->event);
    }

    /**
     * @covers Imbo\EventListener\Cors::invoke
     */
    public function testListenerShouldNotSetAllowedHeadersOnNonOptionsRequest() {
        $listener = new Cors(array(
            'allowedOrigins' => array('*'),
        ));

        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));

        $headers = $this->getMock('Imbo\Http\HeaderContainer');
        $headers->expects($this->at(0))->method('set')->with('Access-Control-Allow-Origin', 'http://imbo-project.org');
        $headers->expects($this->at(1))->method('set')->with('Access-Control-Expose-Headers', 'X-Imbo-Error-Internalcode');

        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($headers));
        $listener->invoke($this->event);
    }

}
