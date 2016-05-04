<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\EventManager;

use Imbo\EventManager\Event,
    Imbo\EventManager\EventManagerInterface,
    Imbo\Http\Request\Request,
    Imbo\Http\Response\Response,
    Imbo\Database\DatabaseInterface,
    Imbo\Storage\StorageInterface;

/**
 * @covers Imbo\EventManager\Event
 * @group unit
 * @group eventmanager
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

    public function getArguments() {
        return [
            'request' => [
                'getRequest', 'request', $this->getMock('Imbo\Http\Request\Request'),
            ],
            'response' => [
                'getResponse', 'response', $this->getMock('Imbo\Http\Response\Response'),
            ],
            'database' => [
                'getDatabase', 'database', $this->getMock('Imbo\Database\DatabaseInterface'),
            ],
            'storage' => [
                'getStorage', 'storage', $this->getMock('Imbo\Storage\StorageInterface'),
            ],
            'accessControl' => [
                'getAccessControl', 'accessControl', $this->getMock('Imbo\Auth\AccessControl\Adapter\AdapterInterface'),
            ],
            'manager' => [
                'getManager', 'manager', $this->getMockBuilder('Imbo\EventManager\EventManager')->disableOriginalConstructor()->getMock(),
            ],
            'config' => [
                'getConfig', 'config', ['some' => 'config'],
            ],
            'handler' => [
                'getHandler', 'handler', 'handler name',
            ],
        ];
    }

    /**
     * @dataProvider getArguments
     */
    public function testCanSetAndGetRequest($method, $argument, $value) {
        $this->event->setArgument($argument, $value);
        $this->assertSame($value, $this->event->$method());
    }

    /**
     * @covers Imbo\EventManager\Event::setName
     * @covers Imbo\EventManager\Event::getName
     */
    public function testCanSetAndGetName() {
        $this->assertNull($this->event->getName());
        $this->assertSame($this->event, $this->event->setName('name'));
        $this->assertSame('name', $this->event->getName());
    }

    /**
     * @covers Imbo\EventManager\Event::stopPropagation
     * @covers Imbo\EventManager\Event::isPropagationStopped
     */
    public function testCanStopPropagation() {
        $this->assertFalse($this->event->isPropagationStopped());
        $this->assertSame($this->event, $this->event->stopPropagation());
        $this->assertTrue($this->event->isPropagationStopped());
    }

    /**
     * @covers Imbo\EventManager\Event::getArgument
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Argument "foobar" does not exist
     * @expectedExceptionCode 500
     */
    public function testThrowsExceptionWhenGettingArgumentThatDoesNotExist() {
        $this->event->getArgument('foobar');
    }

    /**
     * @covers Imbo\EventManager\Event::__construct
     * @covers Imbo\EventManager\Event::setArguments
     */
    public function testCanSetArgumentsThroughConstructor() {
        $event = new Event(['foo' => 'bar']);
        $this->assertSame('bar', $event->getArgument('foo'));
    }

    /**
     * @covers Imbo\EventManager\Event::setArguments
     * @covers Imbo\EventManager\Event::getArgument
     * @covers Imbo\EventManager\Event::hasArgument
     */
    public function testSetArgumentsOverridesAllArguments() {
        $this->assertFalse($this->event->hasArgument('foo'));

        $this->assertSame($this->event, $this->event->setArguments(['foo' => 'bar']));
        $this->assertSame('bar', $this->event->getArgument('foo'));

        $this->assertSame($this->event, $this->event->setArguments(['bar' => 'foo']));
        $this->assertFalse($this->event->hasArgument('foo'));
        $this->assertSame('foo', $this->event->getArgument('bar'));
    }
}
