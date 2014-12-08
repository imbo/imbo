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

use Imbo\EventManager\EventManager,
    Imbo\EventManager\Event,
    Imbo\EventListener\ListenerInterface,
    Imbo\EventListener\Initializer\InitializerInterface;

/**
 * @covers Imbo\EventManager\EventManager
 * @group unit
 * @group eventmanager
 */
class EventManagerTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var EventManager
     */
    private $manager;

    private $request;
    private $event;

    /**
     * Set up the event manager
     */
    public function setUp() {
        $this->request = $this->getMock('Imbo\Http\Request\Request');
        $this->event = new Event($this, array('request' => $this->request));
        $this->manager = new EventManager();
        $this->manager->setEventTemplate($this->event);
    }

    /**
     * Tear down the event manager
     */
    public function tearDown() {
        $this->request = null;
        $this->event = null;
        $this->manager = null;
    }

    /**
     * @covers Imbo\EventManager\EventManager::addEventHandler
     * @covers Imbo\EventManager\EventManager::addCallbacks
     * @covers Imbo\EventManager\EventManager::trigger
     */
    public function testCanRegisterAndExecuteRegularCallbacksInAPrioritizedFashion() {
        $callback1 = function ($event) { echo 1; };
        $callback2 = function ($event) { echo 2; };
        $callback3 = function ($event) { echo 3; };

        $this->assertSame(
            $this->manager,
            $this->manager->addEventHandler('handler1', $callback1)->addCallbacks('handler1', array('event1' => 0))
                          ->addEventHandler('handler2', $callback2)->addCallbacks('handler2', array('event2' => 1))
                          ->addEventHandler('handler3', $callback3)->addCallbacks('handler3', array('event2' => 2))
                          ->addEventHandler('handler4', $callback3)->addCallbacks('handler4', array('event3' => 0))
                          ->addEventHandler('handler5', $callback1)->addCallbacks('handler5', array('event4' => 0))
        );

        $this->expectOutputString('1321');

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
            $event->stopPropagation();
        };

        $this->manager->addEventHandler('handler1', $callback1)->addCallbacks('handler1', array('event' => 3))
                      ->addEventHandler('handler2', $stopper)->addCallbacks('handler2', array('event' => 2))
                      ->addEventHandler('handler3', $callback2)->addCallbacks('handler3', array('event' => 1))
                      ->addEventHandler('handler4', $callback3)->addCallbacks('handler4', array('otherevent' => 0));

        $this->expectOutputString('13');

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
        $this->manager->addEventHandler('handler', function($event) {})->addCallbacks('handler', array('event' => 0));
        $this->assertFalse($this->manager->hasListenersForEvent('some.event'));
        $this->assertTrue($this->manager->hasListenersForEvent('event'));
    }

    /**
     * Fetch users to test filtering
     *
     * @return array[]
     */
    public function getUsers() {
        return array(
            array(null, array(), '1'),
            array(null, array('christer'), '1'),
            array('christer', array('blacklist' => array('christer', 'user')), ''),
            array('christer', array('blacklist' => array('user')), '1'),
            array('christer', array('whitelist' => array('user')), ''),
            array('christer', array('whitelist' => array('christer', 'user')), '1'),
        );
    }

    /**
     * @dataProvider getUsers
     * @covers Imbo\EventManager\EventManager::hasListenersForEvent
     * @covers Imbo\EventManager\EventManager::triggersFor
     */
    public function testCanIncludeAndExcludeUsers($user, $users, $output = '') {
        $callback = function ($event) { echo '1'; };

        $this->manager->addEventHandler('handler', $callback)->addCallbacks('handler', array('event' => 0), $users);

        $this->request->expects($this->any())->method('getUser')->will($this->returnValue($user));

        $this->expectOutputString($output);
        $this->manager->trigger('event');
    }

    /**
     * @covers Imbo\EventManager\EventManager::trigger
     */
    public function testCanAddExtraParametersToTheEvent() {
        $this->manager->addEventHandler('handler', function($event) {
            echo $event->getArgument('foo');
            echo $event->getArgument('bar');
        })->addCallbacks('handler', array('event' => 0));

        $this->expectOutputString('barbaz');

        $this->manager->trigger('event', array(
            'foo' => 'bar',
            'bar' => 'baz',
        ));
    }

    /**
     * @covers Imbo\EventManager\EventManager::addInitializer
     */
    public function testCanInitializeListeners() {
        $listenerClassName = __NAMESPACE__ . '\Listener';
        $this->manager->addInitializer(new Initializer());
        $this->manager->addEventHandler('someHandler', $listenerClassName);
        $this->manager->addCallbacks('someHandler', $listenerClassName::getSubscribedEvents());

        $this->expectOutputString('initeventHandler');
        $this->manager->trigger('event');
    }

    /**
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid event definition for listener: someName
     * @expectedExceptionCode 500
     * @covers Imbo\EventManager\EventManager::addCallbacks
     */
    public function testThrowsExceptionsWhenInvalidHandlersAreAdded() {
        $this->manager->addCallbacks('someName', array('event' => function($event) {}));
    }

    /**
     * @covers Imbo\EventManager\EventManager::addCallbacks
     */
    public function testCanAddMultipleHandlersAtOnce() {
        $listenerClassName = __NAMESPACE__ . '\Listener';
        $this->manager->addEventHandler('someHandler', $listenerClassName);
        $this->manager->addCallbacks('someHandler', $listenerClassName::getSubscribedEvents());

        $this->expectOutputString('bazbarfoo');
        $this->manager->trigger('someEvent');
    }

    /**
     * @covers Imbo\EventManager\EventManager::getHandlerInstance
     */
    public function testCanInjectParamsInConstructor() {
        $listenerClassName = __NAMESPACE__ . '\Listener';
        $this->manager->addEventHandler('someHandler', $listenerClassName, array('param'));
        $this->manager->addCallbacks('someHandler', $listenerClassName::getSubscribedEvents());

        $this->expectOutputString('a:1:{i:0;s:5:"param";}');
        $this->manager->trigger('getParams');
    }
}

class Listener implements ListenerInterface {
    private $params;

    public function __construct(array $params = null) {
        $this->params = $params;
    }

    public static function getSubscribedEvents() {
        return array(
            'event' => 'method',
            'someEvent' => array(
                'foo',
                'baz' => 2,
                'bar' => 1,
            ),
            'getParams' => 'getParams',
        );
    }

    public function getParams($event) {
        echo serialize($this->params);
    }

    public function foo($event) {
        echo 'foo';
    }

    public function bar($event) {
        echo 'bar';
    }

    public function baz($event) {
        echo 'baz';
    }

    public function method($event) {
        echo 'eventHandler';
    }

    public function init() {
        echo 'init';
    }
}

class Initializer implements InitializerInterface {
    public function initialize(ListenerInterface $listener) {
        $listener->init();
    }
}
