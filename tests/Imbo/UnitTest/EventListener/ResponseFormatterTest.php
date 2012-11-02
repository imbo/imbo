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

use Imbo\EventListener\ResponseFormatter,
    Imbo\Container,
    ReflectionProperty;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\EventListener\NotModified
 */
class ResponseFormatterTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Imbo\EventListener\ResponseFormatter
     */
    private $listener;

    /**
     * @var Imbo\EventManager\EventInterface
     */
    private $event;

    /**
     * @var Imbo\Http\Request\RequestInterface
     */
    private $request;

    /**
     * @var Imbo\Http\Response\ResponseInterface
     */
    private $response;

    /**
     * @var Imbo\Http\Response\ResponseWriterInterface
     */
    private $writer;

    /**
     * Set up the listener and the mocks
     */
    public function setUp() {
        $this->request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $this->response = $this->getMock('Imbo\Http\Response\ResponseInterface');
        $this->writer = $this->getMock('Imbo\Http\Response\ResponseWriterInterface');

        $container = new Container();
        $container->request = $this->request;
        $container->response = $this->response;

        $this->event = $this->getMock('Imbo\EventManager\EventInterface');
        $this->event->expects($this->any())->method('getContainer')->will($this->returnValue($container));

        $this->listener = new ResponseFormatter($this->writer);
    }

    /**
     * Tear down all instances used for the tests
     */
    public function tearDown() {
        $this->event = null;
        $this->listener = null;
        $this->request = null;
        $this->response = null;
        $this->writer = null;
    }

    /**
     * @covers Imbo\EventListener\ResponseFormatter::getEvents
     */
    public function testGetEvents() {
        $events = $this->listener->getEvents();
        $expected = array(
            'response.prepare',
        );

        foreach ($expected as $e) {
            $this->assertContains($e, $events);
        }
    }

    /**
     * @covers Imbo\EventListener\ResponseFormatter::invoke
     */
    public function testFormatterShouldNotDoAnythingWhenBodyIsNotAnArray() {
        $this->response->expects($this->once())->method('getBody')->will($this->returnValue('some string'));
        $this->writer->expects($this->never())->method('write');
        $this->listener->invoke($this->event);
    }

    /**
     * @covers Imbo\EventListener\ResponseFormatter::invoke
     */
    public function testFormatterWillWriteWhenBodyIsArray() {
        $body = array('some' => 'value');
        $this->response->expects($this->once())->method('getBody')->will($this->returnValue($body));
        $this->writer->expects($this->once())->method('write')->with($body, $this->request, $this->response, true);
        $this->listener->invoke($this->event);
    }

    /**
     * @covers Imbo\EventListener\ResponseFormatter::invoke
     */
    public function testFormatterWillWriteTwiceIfWriterThrowsException() {
        $body = array('some' => 'value');
        $exception = $this->getMock('Imbo\Exception\RuntimeException');

        $this->response->expects($this->exactly(2))->method('getBody')->will($this->returnValue($body));
        $this->response->expects($this->once())->method('createError')->with($exception, $this->request);

        $this->writer->expects($this->at(0))->method('write')->with($body, $this->request, $this->response, true)->will($this->throwException($exception));
        $this->writer->expects($this->at(1))->method('write')->with($body, $this->request, $this->response, false);

        $this->listener->invoke($this->event);
    }

    /**
     * @covers Imbo\EventListener\ResponseFormatter::__construct
     */
    public function testCreatesAWriterItself() {
        $property = new ReflectionProperty('Imbo\EventListener\ResponseFormatter', 'writer');
        $property->setAccessible(true);

        $listener = new ResponseFormatter();
        $this->assertInstanceOf('Imbo\Http\Response\ResponseWriterInterface', $property->getValue($listener));
    }
}
