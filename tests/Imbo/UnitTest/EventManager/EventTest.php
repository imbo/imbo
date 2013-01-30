<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\UnitTest\EventManager;

use Imbo\EventManager\Event,
    Imbo\EventManager\EventManagerInterface,
    Imbo\Http\Request\RequestInterface,
    Imbo\Http\Response\ResponseInterface,
    Imbo\Database\DatabaseInterface,
    Imbo\Storage\StorageInterface;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Unit tests
 */
class EventTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Event
     */
    private $event;

    private $name = 'some.event.name';
    private $container;
    private $request;
    private $response;
    private $database;
    private $storage;
    private $manager;
    private $config = array('config' => 'value');

    /**
     * Set up the event instance
     *
     * @covers Imbo\EventManager\Event::__construct
     * @covers Imbo\EventManager\Event::setName
     * @covers Imbo\EventManager\Event::setContainer
     */
    public function setUp() {
        $this->request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $this->response = $this->getMock('Imbo\Http\Response\ResponseInterface');
        $this->database = $this->getMock('Imbo\Database\DatabaseInterface');
        $this->storage = $this->getMock('Imbo\Storage\StorageInterface');
        $this->manager = $this->getMock('Imbo\EventManager\EventManagerInterface');
        $this->container = $this->getMock('Imbo\Container');

        $this->event = new Event($this->name);
        $this->event->setContainer($this->container);
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
        $this->event = null;
    }

    /**
     * @covers Imbo\EventManager\Event::__construct
     * @covers Imbo\EventManager\Event::getName
     * @covers Imbo\EventManager\Event::getRequest
     * @covers Imbo\EventManager\Event::getResponse
     * @covers Imbo\EventManager\Event::getDatabase
     * @covers Imbo\EventManager\Event::getStorage
     * @covers Imbo\EventManager\Event::getManager
     * @covers Imbo\EventManager\Event::getConfig
     */
    public function testCanFetchDependencies() {
        $this->container->expects($this->at(0))->method('get')->with('request')->will($this->returnValue($this->request));
        $this->container->expects($this->at(1))->method('get')->with('response')->will($this->returnValue($this->response));
        $this->container->expects($this->at(2))->method('get')->with('database')->will($this->returnValue($this->database));
        $this->container->expects($this->at(3))->method('get')->with('storage')->will($this->returnValue($this->storage));
        $this->container->expects($this->at(4))->method('get')->with('eventManager')->will($this->returnValue($this->manager));
        $this->container->expects($this->at(5))->method('get')->with('config')->will($this->returnValue($this->config));

        $this->assertSame($this->name, $this->event->getName());
        $this->assertSame($this->request, $this->event->getRequest());
        $this->assertSame($this->response, $this->event->getResponse());
        $this->assertSame($this->database, $this->event->getDatabase());
        $this->assertSame($this->storage, $this->event->getStorage());
        $this->assertSame($this->manager, $this->event->getManager());
        $this->assertSame($this->config, $this->event->getConfig());
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
}
