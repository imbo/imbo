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

use Imbo\EventManager\EventManager;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @covers Imbo\EventManager\EventManager
 */
class EventManagerTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var EventManager
     */
    private $manager;

    private $container;

    /**
     * Set up the event manager
     *
     * @covers Imbo\EventManager\EventManager::setContainer
     */
    public function setUp() {
        $this->container = $this->getMock('Imbo\Container');
        $this->manager = new EventManager();
        $this->manager->setContainer($this->container);
    }

    /**
     * Tear down the event manager
     */
    public function tearDown() {
        $this->manager = null;
        $this->container = null;
    }

    /**
     * @covers Imbo\EventManager\EventManager::attach
     * @expectedException InvalidArgumentException
     */
    public function testThrowsExceptionIfCallbackIsNotCallable() {
        $this->manager->attach('event', 'some string');
    }

    /**
     * Callback used in the method below
     */
    public function someCallback($event) {
        echo 3;
    }

    /**
     * @covers Imbo\EventManager\EventManager::attach
     * @covers Imbo\EventManager\EventManager::trigger
     */
    public function testCanAttachAndExecuteRegularCallbacksInAPrioritizedFashion() {
        $callback1 = function ($event) { echo 1; };
        $callback2 = function ($event) { echo 2; };
        $callback3 = array($this, 'someCallback');

        $this->assertSame(
            $this->manager,
            $this->manager->attach('event1', $callback1)
                          ->attach('event2', $callback2, 1)
                          ->attach('event2', $callback3, 2)
                          ->attach('event3', $callback3)
                          ->attach('event4', $callback1)
        );

        $this->expectOutputString('1321');

        $event = $this->getMock('Imbo\EventManager\EventInterface');
        $event->expects($this->at(0))->method('setName')->with('event1');
        $event->expects($this->at(1))->method('propagationIsStopped');
        $event->expects($this->at(2))->method('setName')->with('event2');
        $event->expects($this->at(3))->method('propagationIsStopped');
        $event->expects($this->at(4))->method('propagationIsStopped');
        $event->expects($this->at(5))->method('setName')->with('event4');
        $event->expects($this->at(6))->method('propagationIsStopped');

        $request = $this->getMock('Imbo\Http\Request\Request');
        $request->expects($this->any())->method('getPublicKey')->will($this->returnValue(null));

        $this->container->expects($this->at(0))->method('get')->with('request')->will($this->returnValue($request));
        $this->container->expects($this->at(1))->method('get')->with('event')->will($this->returnValue($event));
        $this->container->expects($this->at(2))->method('get')->with('request')->will($this->returnValue($request));
        $this->container->expects($this->at(3))->method('get')->with('event')->will($this->returnValue($event));
        $this->container->expects($this->at(4))->method('get')->with('request')->will($this->returnValue($request));
        $this->container->expects($this->at(5))->method('get')->with('event')->will($this->returnValue($event));

        $this->manager->trigger('otherevent')
                      ->trigger('event1')
                      ->trigger('event2')
                      ->trigger('event4');
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

        $event = $this->getMock('Imbo\EventManager\EventInterface');
        $event->expects($this->at(0))->method('setName')->with('event');
        $event->expects($this->at(1))->method('propagationIsStopped');
        $event->expects($this->at(2))->method('stopPropagation')->with(true);
        $event->expects($this->at(3))->method('propagationIsStopped')->will($this->returnValue(true));
        $event->expects($this->at(4))->method('setName')->with('otherevent');
        $event->expects($this->at(5))->method('propagationIsStopped');

        $request = $this->getMock('Imbo\Http\Request\Request');
        $request->expects($this->any())->method('getPublicKey')->will($this->returnValue(null));

        $this->container->expects($this->at(0))->method('get')->with('request')->will($this->returnValue($request));
        $this->container->expects($this->at(1))->method('get')->with('event')->will($this->returnValue($event));
        $this->container->expects($this->at(2))->method('get')->with('request')->will($this->returnValue($request));
        $this->container->expects($this->at(3))->method('get')->with('event')->will($this->returnValue($event));

        $this->assertSame(
            $this->manager,
            $this->manager->trigger('event')
                          ->trigger('otherevent')
        );
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
     * Fetch public keys to test filtering
     *
     * @return array[]
     */
    public function getPublicKeys() {
        return array(
            array(null, array(), '1'),
            array(null, array('christer'), '1'),
            array('christer', array('exclude' => array('christer', 'user')), ''),
            array('christer', array('exclude' => array('user')), '1'),
            array('christer', array('include' => array('user')), ''),
            array('christer', array('include' => array('christer', 'user')), '1'),
        );
    }

    /**
     * @dataProvider getPublicKeys
     * @covers Imbo\EventManager\EventManager::hasListenersForEvent
     * @covers Imbo\EventManager\EventManager::triggersFor
     */
    public function testCanIncludeAndExcludePublicKeys($publicKey, $publicKeys, $output = '') {
        $callback = function ($event) { echo '1'; };

        $this->manager->attach('event', $callback, 1, $publicKeys);

        $event = $this->getMock('Imbo\EventManager\EventInterface');
        $event->expects($this->any())->method('setName')->with('event');
        $event->expects($this->any())->method('propagationIsStopped');

        $request = $this->getMock('Imbo\Http\Request\Request');
        $request->expects($this->any())->method('getPublicKey')->will($this->returnValue($publicKey));

        $this->container->expects($this->at(0))->method('get')->with('request')->will($this->returnValue($request));
        $this->container->expects($this->at(1))->method('get')->with('event')->will($this->returnValue($event));

        $this->expectOutputString($output);
        $this->manager->trigger('event');
    }

    /**
     * @covers Imbo\EventManager\EventManager::attachDefinition
     */
    public function testCanAttachListenerDefinitions() {
        $definition = $this->getMockBuilder('Imbo\EventListener\ListenerDefinition')->disableOriginalConstructor()->getMock();
        $definition->expects($this->once())->method('getEventName')->will($this->returnValue('event'));
        $definition->expects($this->once())->method('getCallback')->will($this->returnValue(function($event) { echo '1'; }));
        $definition->expects($this->once())->method('getPriority')->will($this->returnValue(1));
        $definition->expects($this->once())->method('getPublicKeys')->will($this->returnValue(array()));

        $this->manager->attachDefinition($definition);
    }

    /**
     * @covers Imbo\EventManager\EventManager::attachListener
     * @depends testCanAttachListenerDefinitions
     */
    public function testCanAttachListener() {
        $definition = $this->getMockBuilder('Imbo\EventListener\ListenerDefinition')->disableOriginalConstructor()->getMock();
        $definition->expects($this->once())->method('getEventName')->will($this->returnValue('event'));
        $definition->expects($this->once())->method('getCallback')->will($this->returnValue(function($event) { echo '1'; }));
        $definition->expects($this->once())->method('getPriority')->will($this->returnValue(1));
        $definition->expects($this->once())->method('getPublicKeys')->will($this->returnValue(array()));

        $listener = $this->getMock('Imbo\EventListener\ListenerInterface');
        $listener->expects($this->once())->method('getDefinition')->will($this->returnValue(array($definition)));

        $this->manager->attachListener($listener);
    }
}
