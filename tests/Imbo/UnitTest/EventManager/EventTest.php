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

namespace Imbo\UnitTest\EventManager;

use Imbo\EventManager\Event,
    Imbo\EventManager\EventManagerInterface,
    Imbo\Http\Request\RequestInterface,
    Imbo\Http\Response\ResponseInterface,
    Imbo\Database\DatabaseInterface,
    Imbo\Storage\StorageInterface;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\EventManager\Event
 */
class EventTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var string
     */
    private $name = 'some.event.name';

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var DatabaseInterface
     */
    private $database;

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var EventManagerInterface
     */
    private $manager;

    /**
     * @var array
     */
    private $params = array('key' => 'value');

    /**
     * Set up the event instance
     */
    public function setUp() {
        $this->request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $this->response = $this->getMock('Imbo\Http\Response\ResponseInterface');
        $this->database = $this->getMock('Imbo\Database\DatabaseInterface');
        $this->storage = $this->getMock('Imbo\Storage\StorageInterface');
        $this->manager = $this->getMock('Imbo\EventManager\EventManagerInterface');

        $this->event = new Event($this->name, $this->request, $this->response, $this->database,
                                 $this->storage, $this->manager, $this->params);
    }

    /**
     * Set up the event instance
     */
    public function tearDown() {
        $this->event = null;
        $this->request = null;
        $this->response = null;
        $this->database = null;
        $this->storage = null;
        $this->manager = null;
    }

    /**
     * @covers Imbo\EventManager\Event::__construct
     * @covers Imbo\EventManager\Event::getName
     * @covers Imbo\EventManager\Event::getRequest
     * @covers Imbo\EventManager\Event::getResponse
     * @covers Imbo\EventManager\Event::getDatabase
     * @covers Imbo\EventManager\Event::getStorage
     * @covers Imbo\EventManager\Event::getManager
     * @covers Imbo\EventManager\Event::getParams
     */
    public function testEvent() {
        $this->assertSame($this->name, $this->event->getName());
        $this->assertSame($this->request, $this->event->getRequest());
        $this->assertSame($this->response, $this->event->getResponse());
        $this->assertSame($this->database, $this->event->getDatabase());
        $this->assertSame($this->storage, $this->event->getStorage());
        $this->assertSame($this->manager, $this->event->getManager());
        $this->assertSame($this->params, $this->event->getParams());
    }

    /**
     * @covers Imbo\EventManager\Event::propagationIsStopped
     * @covers Imbo\EventManager\Event::stopPropagation
     */
    public function testPropagationCanBeStopped() {
        $this->assertFalse($this->event->propagationIsStopped());
        $this->assertSame($this->event, $this->event->stopPropagation(true));
        $this->assertTrue($this->event->propagationIsStopped());
        $this->assertSame($this->event, $this->event->stopPropagation(false));
        $this->assertFalse($this->event->propagationIsStopped());
    }

    /**
     * @covers Imbo\EventManager\Event::applicationIsHalted
     * @covers Imbo\EventManager\Event::haltApplication
     */
    public function testApplicationCanBeHalted() {
        $this->assertFalse($this->event->applicationIsHalted());
        $this->assertSame($this->event, $this->event->haltApplication(true));
        $this->assertTrue($this->event->applicationIsHalted());
        $this->assertSame($this->event, $this->event->haltApplication(false));
        $this->assertFalse($this->event->applicationIsHalted());
    }
}
