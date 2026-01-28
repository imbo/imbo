<?php declare(strict_types=1);

namespace Imbo\EventManager;

use Closure;
use Imbo\EventListener\Initializer\InitializerInterface;
use Imbo\EventListener\ListenerInterface;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Http\Request\Request;
use Imbo\Http\Response\Response;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(EventManager::class)]
class EventManagerTest extends TestCase
{
    private EventManager $manager;
    private Request&MockObject $request;
    private Event $event;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
        $this->event = new Event(['request' => $this->request]);
        $this->manager = new EventManager();
        $this->manager->setEventTemplate($this->event);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testCanRegisterAndExecuteRegularCallbacksInAPrioritizedFashion(): void
    {
        $callback1 = static function (EventInterface $event): void {
            echo 1;
        };
        $callback2 = static function (EventInterface $event): void {
            echo 2;
        };
        $callback3 = static function (EventInterface $event): void {
            echo 3;
        };

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
                ->addCallbacks('handler5', ['event4' => 0]),
        );

        $this->expectOutputString('1321');

        $this->manager
            ->trigger('otherevent')
            ->trigger('event1')
            ->trigger('event2')
            ->trigger('event4');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testLetsListenerStopPropagation(): void
    {
        $callback1 = static function (EventInterface $event): void {
            echo 1;
        };
        $callback2 = static function (EventInterface $event): void {
            echo 2;
        };
        $callback3 = static function (EventInterface $event): void {
            echo 3;
        };
        $stopper = static function (EventInterface $event): void {
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
                ->trigger('otherevent'),
        );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testCanCheckIfTheManagerHasListenersForSpecificEvents(): void
    {
        $this->manager
            ->addEventHandler('handler', static function (EventInterface $event): void {})
            ->addCallbacks('handler', ['event' => 0]);

        $this->assertFalse($this->manager->hasListenersForEvent('some.event'));
        $this->assertTrue($this->manager->hasListenersForEvent('event'));
    }

    #[DataProvider('getUsers')]
    #[AllowMockObjectsWithoutExpectations]
    public function testCanIncludeAndExcludeUsers(string $user, array $users, bool $willTrigger): void
    {
        $check = new stdClass();
        $check->triggered = false;

        $callback = static function (EventInterface $event) use ($check): void {
            $check->triggered = true;
        };

        $this->manager
            ->addEventHandler('handler', $callback)
            ->addCallbacks('handler', ['event' => 0], $users);

        if ('' !== $user) {
            $this->request
                ->expects($this->once())
                ->method('getUser')
                ->willReturn($user);
        }

        $this->manager->trigger('event');
        $this->assertSame($willTrigger, $check->triggered);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testCanAddExtraParametersToTheEvent(): void
    {
        $this->manager->addEventHandler('handler', static function (EventInterface $event): void {
            echo (string) $event->getArgument('foo');
            echo (string) $event->getArgument('bar');
        })->addCallbacks('handler', ['event' => 0]);

        $this->expectOutputString('barbaz');

        $this->manager->trigger('event', [
            'foo' => 'bar',
            'bar' => 'baz',
        ]);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testCanInitializeListeners(): void
    {
        $this->manager
            ->addInitializer($i = new Initializer())
            ->addEventHandler('someHandler', Listener::class)
            ->addCallbacks('someHandler', Listener::getSubscribedEvents());

        $this->expectOutputString('eventHandler');
        $this->manager->trigger('event');
        $this->assertSame([$i], $this->manager->getInitializers());
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionsWhenInvalidHandlersAreAdded(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException(
            'Invalid event definition for listener: someName',
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ));
        $callback = static function (EventInterface $event): void {};
        $this->manager->addCallbacks('someName', ['event' => $callback]);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testCanAddMultipleHandlersAtOnce(): void
    {
        $this->manager
            ->addEventHandler('someHandler', Listener::class)
            ->addCallbacks('someHandler', Listener::getSubscribedEvents());

        $this->expectOutputString('bazbarfoo');
        $this->manager->trigger('someEvent');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testCanInjectParamsInConstructor(): void
    {
        $this->manager
            ->addEventHandler('someHandler', Listener::class, ['param'])
            ->addCallbacks('someHandler', Listener::getSubscribedEvents());

        $this->expectOutputString('a:1:{i:0;s:5:"param";}');
        $this->manager->trigger('getParams');
    }

    #[DataProvider('getWildcardListeners')]
    #[AllowMockObjectsWithoutExpectations]
    public function testSupportsWildcardListeners(array $listeners, array $events, string $output): void
    {
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

    #[AllowMockObjectsWithoutExpectations]
    public function testCanSetEventTemplate(): void
    {
        $this->expectOutputString('bar');
        (new EventManager())
            ->setEventTemplate(new Event(['foo' => 'bar', 'request' => $this->createStub(Request::class)]))
            ->addEventHandler('handler', static function (EventInterface $event): void {
                echo (string) $event->getArgument('foo');
            })
            ->addCallbacks('handler', ['event' => 0])
            ->trigger('event');
    }

    /**
     * @return array<array{user:string,users:array,willTrigger:bool}>
     */
    public static function getUsers(): array
    {
        return [
            'no specified user and empty filter, will trigger' => [
                'user' => '',
                'users' => [],
                'willTrigger' => true,
            ],
            'no specified user and non-empty filter, will trigger' => [
                'user' => '',
                'users' => ['christer'],
                'willTrigger' => true,
            ],
            'user in blacklist, will not trigger' => [
                'user' => 'christer',
                'users' => ['blacklist' => ['christer', 'user']],
                'willTrigger' => false,
            ],
            'user not in blacklist, will trigger' => [
                'user' => 'christer',
                'users' => ['blacklist' => ['user']],
                'willTrigger' => true,
            ],
            'user not in whitelist, will not trigger' => [
                'user' => 'christer',
                'users' => ['whitelist' => ['user']],
                'willTrigger' => false,
            ],
            'user in whitelist, will trigger' => [
                'user' => 'christer',
                'users' => ['whitelist' => ['christer', 'user']],
                'willTrigger' => true,
            ],
        ];
    }

    /**
     * @return array<array{listeners:array<array{callback:Closure,event:string,priority:int}>,events:array<string>,output:string}>
     */
    public static function getWildcardListeners(): array
    {
        $callback1 = static function (EventInterface $event): void {
            echo '1:'.(string) $event->getName().' ';
        };
        $callback2 = static function (EventInterface $event): void {
            echo '2:'.(string) $event->getName().' ';
        };
        $callback3 = static function (EventInterface $event): void {
            echo '3:'.(string) $event->getName().' ';
        };

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
}

class Listener implements ListenerInterface
{
    private ?array $params;

    public function __construct(?array $params = null)
    {
        $this->params = $params;
    }

    public static function getSubscribedEvents(): array
    {
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

    public function getParams(EventInterface $event): void
    {
        echo serialize($this->params);
    }

    public function foo(EventInterface $event): void
    {
        echo 'foo';
    }

    public function bar(EventInterface $event): void
    {
        echo 'bar';
    }

    public function baz(EventInterface $event): void
    {
        echo 'baz';
    }

    public function method(EventInterface $event): void
    {
        echo 'eventHandler';
    }
}

class Initializer implements InitializerInterface
{
    public function initialize(ListenerInterface $listener): void
    {
    }
}
