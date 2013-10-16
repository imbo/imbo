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
    Imbo\Http\Request\Request,
    Imbo\Http\Response\Response,
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

    /**
     * Set up the event instance
     */
    public function setUp() {
        $this->event = new Event();
    }

    /**
     * Set up the event instance
     */
    public function tearDown() {
        $this->event = null;
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
     * @covers Imbo\EventManager\Event::getName
     * @covers Imbo\EventManager\Event::setName
     */
    public function testCanSetAndGetName() {
        $this->assertNull($this->event->getName());
        $this->assertSame($this->event, $this->event->setName('name'));
        $this->assertSame('name', $this->event->getName());
    }

    /**
     * @covers Imbo\EventManager\Event::getRequest
     * @covers Imbo\EventManager\Event::setRequest
     */
    public function testCanSetAndGetRequest() {
        $this->assertNull($this->event->getRequest());
        $request = $this->getMock('Imbo\Http\Request\Request');
        $this->assertSame($this->event, $this->event->setRequest($request));
        $this->assertSame($request, $this->event->getRequest());
    }

    /**
     * @covers Imbo\EventManager\Event::getResponse
     * @covers Imbo\EventManager\Event::setResponse
     */
    public function testCanSetAndGetResponse() {
        $this->assertNull($this->event->getResponse());
        $response = $this->getMock('Imbo\Http\Response\Response');
        $this->assertSame($this->event, $this->event->setResponse($response));
        $this->assertSame($response, $this->event->getResponse());
    }

    /**
     * @covers Imbo\EventManager\Event::getDatabase
     * @covers Imbo\EventManager\Event::setDatabase
     */
    public function testCanSetAndGetDatabase() {
        $this->assertNull($this->event->getDatabase());
        $adapter = $this->getMock('Imbo\Database\DatabaseInterface');
        $this->assertSame($this->event, $this->event->setDatabase($adapter));
        $this->assertSame($adapter, $this->event->getDatabase());
    }

    /**
     * @covers Imbo\EventManager\Event::getStorage
     * @covers Imbo\EventManager\Event::setStorage
     */
    public function testCanSetAndGetStorage() {
        $this->assertNull($this->event->getStorage());
        $adapter = $this->getMock('Imbo\Storage\StorageInterface');
        $this->assertSame($this->event, $this->event->setStorage($adapter));
        $this->assertSame($adapter, $this->event->getStorage());
    }

    /**
     * @covers Imbo\EventManager\Event::getManager
     * @covers Imbo\EventManager\Event::setManager
     */
    public function testCanSetAndGetEventManager() {
        $this->assertNull($this->event->getManager());
        $manager = $this->getMockBuilder('Imbo\EventManager\EventManager')->disableOriginalConstructor()->getMock();
        $this->assertSame($this->event, $this->event->setManager($manager));
        $this->assertSame($manager, $this->event->getManager());
    }

    /**
     * @covers Imbo\EventManager\Event::getConfig
     * @covers Imbo\EventManager\Event::setConfig
     */
    public function testCanSetAndGetConfig() {
        $this->assertNull($this->event->getConfig());
        $config = array('foo' => 'bar');
        $this->assertSame($this->event, $this->event->setConfig($config));
        $this->assertSame($config, $this->event->getConfig());
    }

    /**
     * @covers Imbo\EventManager\Event::getHandler
     * @covers Imbo\EventManager\Event::setHandler
     */
    public function testCanSetAndGetEventHandlerName() {
        $this->assertNull($this->event->getHandler());
        $this->assertSame($this->event, $this->event->setHandler('handler'));
        $this->assertSame('handler', $this->event->getHandler());
    }
}
