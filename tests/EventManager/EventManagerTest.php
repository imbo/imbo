<?php declare(strict_types=1);
namespace Imbo\EventManager;

use Imbo\EventManager\Event;
use Imbo\EventListener\ListenerInterface;
use Imbo\EventListener\Initializer\InitializerInterface;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Http\Request\Request;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\EventManager\EventManager
 */
class EventManagerTest extends TestCase {
    private $manager;
    private $request;
    private $event;

    public function setUp() : void {
        $this->request = $this->createMock(Request::class);
        $this->event = new Event(['request' => $this->request]);
        $this->manager = new EventManager();
        $this->manager->setEventTemplate($this->event);
    }

    /**
     * @covers ::addEventHandler
     * @covers ::addCallbacks
     * @covers ::trigger
     */
    public function testCanRegisterAndExecuteRegularCallbacksInAPrioritizedFashion() : void {
        $callback1 = function ($event) { echo 1; };
        $callback2 = function ($event) { echo 2; };
        $callback3 = function ($event) { echo 3; };

        $this->assertSame(
            $this->manager,
            $this->manager
                ->addEventHandler('handler1', $callback1)
                ->addCallbacks('handler1', ['event1' => 0])

                ->addEventHandler('handler2', $callback2)
                ->addCallbacks('handler2', ['event2' => 1])

                ->addEventHandler('handler3', $callback3)
                ->addCallbacks('handler3', ['event2' => 2])

                ->addEventHandler('handler4', $callback3)
                ->addCallbacks('handler4', ['event3' => 0])

                ->addEventHandler('handler5', $callback1)
                ->addCallbacks('handler5', ['event4' => 0])
        );

        $this->expectOutputString('1321');

        $this->manager
            ->trigger('otherevent')
            ->trigger('event1')
            ->trigger('event2')
            ->trigger('event4');
    }

    /**
     * @covers ::trigger
     */
    public function testLetsListenerStopPropagation() : void {
        $callback1 = function($event) { echo 1; };
        $callback2 = function($event) { echo 2; };
        $callback3 = function($event) { echo 3; };
        $stopper = function($event) {
            $event->stopPropagation();
        };

        $this->manager
            ->addEventHandler('handler1', $callback1)
            ->addCallbacks('handler1', ['event' => 3])

            ->addEventHandler('handler2', $stopper)
            ->addCallbacks('handler2', ['event' => 2])

            ->addEventHandler('handler3', $callback2)
            ->addCallbacks('handler3', ['event' => 1])

            ->addEventHandler('handler4', $callback3)
            ->addCallbacks('handler4', ['otherevent' => 0]);

        $this->expectOutputString('13');

        $this->assertSame(
            $this->manager,
            $this->manager
                ->trigger('event')
                ->trigger('otherevent')
        );
    }

    /**
     * @covers ::hasListenersForEvent
     */
    public function testCanCheckIfTheManagerHasListenersForSpecificEvents() : void {
        $this->manager
            ->addEventHandler('handler', function($event) {})
            ->addCallbacks('handler', ['event' => 0]);

        $this->assertFalse($this->manager->hasListenersForEvent('some.event'));
        $this->assertTrue($this->manager->hasListenersForEvent('event'));
    }

    public function getUsers() : array {
        return [
            [null, [], '1'],
            [null, ['christer'], '1'],
            ['christer', ['blacklist' => ['christer', 'user']], ''],
            ['christer', ['blacklist' => ['user']], '1'],
            ['christer', ['whitelist' => ['user']], ''],
            ['christer', ['whitelist' => ['christer', 'user']], '1'],
        ];
    }

    /**
     * @dataProvider getUsers
     * @covers ::hasListenersForEvent
     * @covers ::triggersFor
     * @covers ::trigger
     */
    public function testCanIncludeAndExcludeUsers(?string $user, array $users, string $output = '') : void {
        $callback = function ($event) { echo '1'; };

        $this->manager
            ->addEventHandler('handler', $callback)
            ->addCallbacks('handler', ['event' => 0], $users);

        $this->request
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        $this->expectOutputString($output);
        $this->manager->trigger('event');
    }

    /**
     * @covers ::trigger
     */
    public function testCanAddExtraParametersToTheEvent() : void {
        $this->manager->addEventHandler('handler', function($event) {
            echo $event->getArgument('foo');
            echo $event->getArgument('bar');
        })->addCallbacks('handler', ['event' => 0]);

        $this->expectOutputString('barbaz');

        $this->manager->trigger('event', [
            'foo' => 'bar',
            'bar' => 'baz',
        ]);
    }

    /**
     * @covers ::addInitializer
     * @covers ::getInitializers
     * @covers ::getHandlerInstance
     * @covers ::trigger
     */
    public function testCanInitializeListeners() : void {
        $listenerClassName = __NAMESPACE__ . '\Listener';
        $this->manager
            ->addInitializer($i = new Initializer())
            ->addEventHandler('someHandler', $listenerClassName)
            ->addCallbacks('someHandler', $listenerClassName::getSubscribedEvents());

        $this->expectOutputString('initeventHandler');
        $this->manager->trigger('event');
        $this->assertSame([$i], $this->manager->getInitializers());
    }

    /**
     * @covers ::addCallbacks
     */
    public function testThrowsExceptionsWhenInvalidHandlersAreAdded() : void {
        $this->expectExceptionObject(new InvalidArgumentException(
            'Invalid event definition for listener: someName',
            500
        ));
        $this->manager->addCallbacks('someName', ['event' => function($event) {}]);
    }

    /**
     * @covers ::addCallbacks
     * @covers ::addEventHandler
     */
    public function testCanAddMultipleHandlersAtOnce() : void {
        $listenerClassName = __NAMESPACE__ . '\Listener';
        $this->manager
            ->addEventHandler('someHandler', $listenerClassName)
            ->addCallbacks('someHandler', $listenerClassName::getSubscribedEvents());

        $this->expectOutputString('bazbarfoo');
        $this->manager->trigger('someEvent');
    }

    /**
     * @covers ::getHandlerInstance
     */
    public function testCanInjectParamsInConstructor() : void {
        $listenerClassName = __NAMESPACE__ . '\Listener';
        $this->manager
            ->addEventHandler('someHandler', $listenerClassName, ['param'])
            ->addCallbacks('someHandler', $listenerClassName::getSubscribedEvents());

        $this->expectOutputString('a:1:{i:0;s:5:"param";}');
        $this->manager->trigger('getParams');
    }

    public function getWildcardListeners() : array {
        $callback1 = function($event) { echo '1:' . $event->getName() . ' '; };
        $callback2 = function($event) { echo '2:' . $event->getName() . ' '; };
        $callback3 = function($event) { echo '3:' . $event->getName() . ' '; };

        return [
            'global wildcard listeners' => [
                'listeners' => [
                    [
                        'callback' => $callback1,
                        'event' => '*',
                        'priority' => 0,
                    ],
                    [
                        'callback' => $callback2,
                        'event' => '*',
                        'priority' => 1,
                    ],
                ],
                'events' => ['foo', 'bar', 'baz'],
                'output' => '2:foo 1:foo 2:bar 1:bar 2:baz 1:baz ',
            ],
            'mixed listeners' => [
                'listeners' => [
                    [
                        'callback' => $callback1,
                        'event' => '*',
                        'priority' => 0,
                    ],
                    [
                        'callback' => $callback2,
                        'event' => 'image.*',
                        'priority' => 0,
                    ],
                    [
                        'callback' => $callback3,
                        'event' => 'image.get',
                        'priority' => 100, // This has higher priority than callback2 above, but is
                                           // still triggered last (because wildcard listeners run
                                           // in their own queues, and is triggerd first
                    ],
                ],
                'events' => ['app.start', 'image.get', 'image.send'],
                'output' => '1:app.start 1:image.get 2:image.get 3:image.get 1:image.send 2:image.send ',
            ],
        ];
    }

    /**
     * @dataProvider getWildcardListeners
     * @covers ::getListenersForEvent
     * @covers ::getEventNameParts
     */
    public function testSupportsWildcardListeners(array $listeners, array $events, string $output) : void {
        foreach ($listeners as $name => $listener) {
            $this->manager
                ->addEventHandler($name, $listener['callback'])
                ->addCallbacks($name, [$listener['event'] => $listener['priority']]);
        }

        $this->expectOutputString($output);

        foreach ($events as $event) {
            $this->manager->trigger($event);
        }
    }

    /**
     * @covers ::setEventTemplate
     */
    public function testCanSetEventTemplate() : void {
        $this->expectOutputString('bar');
        (new EventManager())
            ->setEventTemplate(new Event(['foo' => 'bar', 'request' => $this->createMock(Request::class)]))
            ->addEventHandler('handler', function ($event) { echo $event->getArgument('foo'); })
            ->addCallbacks('handler', ['event' => 0])
            ->trigger('event');
    }
}

class Listener implements ListenerInterface {
    private $params;

    public function __construct(array $params = null) {
        $this->params = $params;
    }

    public static function getSubscribedEvents() {
        return [
            'event' => 'method',
            'someEvent' => [
                'foo',
                'baz' => 2,
                'bar' => 1,
            ],
            'getParams' => 'getParams',
        ];
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
