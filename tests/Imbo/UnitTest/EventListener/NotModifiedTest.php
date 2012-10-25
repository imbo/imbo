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

use Imbo\EventListener\NotModified,
    Imbo\Container;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\EventListener\NotModified
 */
class NotModifiedTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Imbo\EventListener\NotModified
     */
    private $listener;

    /**
     * @var Imbo\EventManager\EventInterface
     */
    private $event;

    /**
     * @var Imbo\Http\HeaderContainerInterface
     */
    private $requestHeaders;

    /**
     * @var Imbo\Http\Response\ResponseInterface
     */
    private $response;

    /**
     * @var Imbo\Http\HeaderContainerInterface
     */
    private $responseHeaders;

    /**
     * Set up the listener and the mocks
     */
    public function setUp() {
        $this->requestHeaders = $this->getMock('Imbo\Http\ParameterContainerInterface');
        $this->responseHeaders = $this->getMock('Imbo\Http\ParameterContainerInterface');

        $request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $request->expects($this->any())->method('getHeaders')->will($this->returnValue($this->requestHeaders));

        $this->response = $this->getMock('Imbo\Http\Response\ResponseInterface');
        $this->response->expects($this->any())->method('getHeaders')->will($this->returnValue($this->responseHeaders));

        $container = new Container();
        $container->request = $request;
        $container->response = $this->response;

        $this->event = $this->getMock('Imbo\EventManager\EventInterface');
        $this->event->expects($this->any())->method('getContainer')->will($this->returnValue($container));

        $this->listener = new NotModified();
    }

    /**
     * Tear down all instances used for the tests
     */
    public function tearDown() {
        $this->requestHeaders = null;
        $this->responseHeaders = null;
        $this->response = null;
        $this->event = null;
        $this->listener = null;
    }

    /**
     * @covers Imbo\EventListener\NotModified::getEvents
     */
    public function testGetEvents() {
        $events = $this->listener->getEvents();
        $expected = array(
            'response.send',
        );

        foreach ($expected as $e) {
            $this->assertContains($e, $events);
        }
    }

    /**
     * @covers Imbo\EventListener\NotModified::invoke
     */
    public function testMarksResponseAsNotModifiedWhenRequestAndResponseHeadersMatch() {
        $etag = '"0e4a13b33f3509f74e120340fdb98d33"';
        $lastModified = 'Fri, 19 Oct 2012 12:35:21 GMT';

        $this->requestHeaders->expects($this->any())
                             ->method('get')
                             ->will($this->returnCallback(
        function($key) use ($etag, $lastModified) {
            if ($key === 'if-modified-since') {
                return $lastModified;
            } else if ($key === 'if-none-match') {
                return $etag;
            }
        }));

        $this->responseHeaders->expects($this->any())
                              ->method('get')
                              ->will($this->returnCallback(
        function($key) use ($etag, $lastModified) {
            if ($key === 'last-modified') {
                return $lastModified;
            } else if ($key === 'etag') {
                return $etag;
            }
        }));

        $this->response->expects($this->once())->method('setNotModified');
        $this->listener->invoke($this->event);
    }

    /**
     * @covers Imbo\EventListener\NotModified::invoke
     */
    public function testDoesNotTouchResponseWhenRequestAndResponseHeadersDoesNotMatch() {
        $etag = '"0e4a13b33f3509f74e120340fdb98d33"';
        $lastModified = 'Fri, 19 Oct 2012 12:35:21 GMT';
        $ifNoneMatch = '"1b62512d420456c92fd67abbb2c22c2f"';
        $ifModifiedSince = 'Fri, 19 Oct 2012 12:35:21 GMT';


        $this->requestHeaders->expects($this->any())
                             ->method('get')
                             ->will($this->returnCallback(
        function($key) use ($ifModifiedSince, $ifNoneMatch) {
            if ($key === 'if-modified-since') {
                return $ifModifiedSince;
            } else if ($key === 'if-none-match') {
                return $ifNoneMatch;
            }
        }));

        $this->responseHeaders->expects($this->any())
                              ->method('get')
                              ->will($this->returnCallback(
        function($key) use ($etag, $lastModified) {
            if ($key === 'last-modified') {
                return $lastModified;
            } else if ($key === 'etag') {
                return $etag;
            }
        }));

        $this->response->expects($this->never())->method('setNotModified');
        $this->listener->invoke($this->event);
    }
}
