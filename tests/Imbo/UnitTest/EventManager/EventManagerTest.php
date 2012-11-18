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

use Imbo\Http\Request\RequestInterface,
    Imbo\EventManager\EventManager,
    Imbo\EventManager\EventManagerInterface;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\EventManager\EventManager
 */
class EventManagerTest extends \PHPUnit_Framework_TestCase {
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
     * @var array
     */
    private $config = array('config' => 'value');

    /**
     * Set up the event manager
     *
     * @covers Imbo\EventManager\EventManager::__construct
     */
    public function setUp() {
        $this->request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $this->response = $this->getMock('Imbo\Http\Response\ResponseInterface');
        $this->database = $this->getMock('Imbo\Database\DatabaseInterface');
        $this->storage = $this->getMock('Imbo\Storage\StorageInterface');

        $this->manager = new EventManager(
            $this->request, $this->response, $this->database, $this->storage, $this->config
        );
    }

    /**
     * Tear down the event manager
     */
    public function tearDown() {
        $this->manager = null;
        $this->request = null;
        $this->response = null;
        $this->database = null;
        $this->storage = null;
    }

    /**
     * @covers Imbo\EventManager\EventManager::attach
     * @expectedException InvalidArgumentException
     */
    public function testThrowsExceptionIfCallbackIsNotCallable() {
        $this->manager->attach('event', 'some string');
    }

    /**
     * @covers Imbo\EventManager\EventManager::attach
     * @covers Imbo\EventManager\EventManager::trigger
     */
    public function testCanAttachAndExecuteRegularCallbacksInAPrioritizedFashion() {
        $callback1 = function ($event) { echo 1; };
        $callback2 = function ($event) { echo 2; };
        $callback3 = function ($event) { echo 3; };

        $this->assertSame(
            $this->manager,
            $this->manager->attach('event1', $callback1)
                          ->attach('event2', $callback2, 1)
                          ->attach('event2', $callback3, 2)
                          ->attach('event3', $callback3)
                          ->attach('event4', $callback1)
        );

        $this->expectOutputString('1321');

        $this->manager->trigger('otherevent')
                      ->trigger('event1')
                      ->trigger('event2')
                      ->trigger('event4');
    }

    /**
     * @covers Imbo\EventManager\EventManager::attachListener
     * @covers Imbo\EventManager\EventManager::trigger
     */
    public function testCanAttachAndExecuteListenersInAPrioritizedFashion() {
        $listener1 = $this->getMockBuilder('Imbo\EventListener\ListenerInterface')
                         ->setMethods(array('getEvents', 'onEventName'))
                         ->getMock();
        $listener1->expects($this->once())
                  ->method('getEvents')
                  ->will($this->returnValue(array('event.name')));
        $listener1->expects($this->once())
                  ->method('onEventName')
                  ->with($this->isInstanceOf('Imbo\EventManager\EventInterface'))
                  ->will($this->returnCallback(function() { echo 1; }));

        $listener2 = $this->getMockBuilder('Imbo\EventListener\ListenerInterface')
                         ->setMethods(array('getEvents', 'onEventName'))
                         ->getMock();
        $listener2->expects($this->once())
                  ->method('getEvents')
                  ->will($this->returnValue(array('event.name')));
        $listener2->expects($this->once())
                  ->method('onEventName')
                  ->with($this->isInstanceOf('Imbo\EventManager\EventInterface'))
                  ->will($this->returnCallback(function() { echo 2; }));

        $this->expectOutputString("21");

        $this->assertSame(
            $this->manager,
            $this->manager->attachListener($listener1, 10)
                          ->attachListener($listener2, 20)
                          ->trigger('event.name')
        );
    }

    /**
     * @covers Imbo\EventManager\EventManager::trigger
     */
    public function testLetsListenerStopPropagation() {
        $callback1 = function($event) { echo 1; };
        $callback2 = function($event) { echo 2; };
        $callback3 = function($event) { echo 3; };
        $stopper = function($event) {
            $event->stopPropagation(true);
        };

        $this->manager->attach('event', $callback1, 3)
                      ->attach('event', $stopper, 2)
                      ->attach('event', $callback2, 1)
                      ->attach('otherevent', $callback3);

        $this->expectOutputString('13');
        $this->assertSame(
            $this->manager,
            $this->manager->trigger('event')
                          ->trigger('otherevent')
        );
    }

    /**
     * @covers Imbo\EventManager\EventManager::trigger
     * @expectedException Imbo\Exception\HaltApplication
     */
    public function testHaltApplicationExceptionShouldBeThrownWhenEventListenerHaltsApplication() {
        $this->manager->attach('event', function($event) { $event->haltApplication(true); });
        $this->manager->trigger('event');
    }

    /**
     * @covers Imbo\EventManager\EventManager::trigger
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage can not execute "event.foo"
     */
    public function testThrowsExceptionWhenListenerIsMissingEventMethod() {
        $listener = $this->getMockBuilder('Imbo\EventListener\ListenerInterface')->setMethods(array('getEvents'))->getMock();
        $listener->expects($this->once())->method('getEvents')->will($this->returnValue(array('event.foo')));

        $this->manager->attachListener($listener);
        $this->manager->trigger('event.foo');
    }

    /**
     * @covers Imbo\EventManager\EventManager::hasListenersForEvent
     */
    public function testCanCheckIfTheManagerHasListenersForSpecificEvents() {
        $this->manager->attach('event', function($event) {});
        $this->assertFalse($this->manager->hasListenersForEvent('some.event'));
        $this->assertTrue($this->manager->hasListenersForEvent('event'));
    }

    /**
     * @covers Imbo\EventManager\EventManager::attachListener
     */
    public function testWillNotTriggerPublicKeyAwareListenersIfTheyDoNotContainTheCurrentPublicKey() {
        $publicKey = 'key';
        $this->request->expects($this->exactly(2))->method('getPublicKey')->will($this->returnValue($publicKey));

        $listener1 = $this->getMockBuilder('Imbo\EventListener\PublicKeyAwareListenerInterface')
                          ->setMethods(array('getEvents', 'setPublicKeys', 'triggersFor'))
                          ->getMock();
        $listener1->expects($this->once())
                  ->method('triggersFor')
                  ->with($publicKey)
                  ->will($this->returnValue(false));

        $listener2 = $this->getMockBuilder('Imbo\EventListener\PublicKeyAwareListenerInterface')
                          ->setMethods(
                              array('getEvents', 'setPublicKeys', 'triggersFor', 'onEvent')
                          )
                          ->getMock();
        $listener2->expects($this->once())
                  ->method('triggersFor')
                  ->with($publicKey)
                  ->will($this->returnValue(true));
        $listener2->expects($this->once())
                  ->method('getEvents')
                  ->will($this->returnValue(array('event')));
        $listener2->expects($this->once())->method('onEvent');

        $this->manager->attachListener($listener1)
                      ->attachListener($listener2)
                      ->trigger('event');
    }
}
