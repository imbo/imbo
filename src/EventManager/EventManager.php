<?php declare(strict_types=1);
namespace Imbo\EventManager;

use Imbo\EventListener\Initializer\InitializerInterface;
use Imbo\EventListener\ListenerInterface;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Http\Response\Response;

class EventManager
{
    private array $eventHandlers = [];
    private EventInterface $event;
    private array $callbacks = [];

    /**
     * @var array<InitializerInterface>
     */
    private $initializers = [];

    /**
     * Register an event handler
     *
     * @param mixed $handler The handler itself
     */
    public function addEventHandler($name, $handler, array $params = []): self
    {
        if (is_string($handler)) {
            $this->eventHandlers[$name] = [
                'handler' => $handler,
                'params' => $params,
            ];
        } else {
            $this->eventHandlers[$name] = $handler;
        }

        return $this;
    }

    /**
     * Add one or more callbacks
     */
    public function addCallbacks($name, array $events, array $users = []): self
    {
        $defaultPriority = 0;

        foreach ($events as $event => $callback) {
            if (!isset($this->callbacks[$event])) {
                // Create a priority queue for this event
                $this->callbacks[$event] = new PriorityQueue();
            }

            if (is_string($callback)) {
                // 'eventName' => 'someMethod'
                $this->callbacks[$event]->insert([
                    'handler' => $name,
                    'method' => $callback,
                    'users' => $users,
                ], $defaultPriority);
            } elseif (is_array($callback)) {
                // 'eventName' => [ ... ]
                foreach ($callback as $method => $priority) {
                    if (is_int($method)) {
                        // 'eventName' => ['someMethod', ...]
                        $method = $priority;
                        $priority = $defaultPriority;
                    }

                    $this->callbacks[$event]->insert([
                        'handler' => $name,
                        'method' => $method,
                        'users' => $users,
                    ], $priority);
                }
            } elseif (is_int($callback)) {
                // We have a closure as a callback, so $callback is the actual priority
                $this->callbacks[$event]->insert([
                    'handler' => $name,
                    'users' => $users,
                ], $callback);
            } else {
                throw new InvalidArgumentException(
                    'Invalid event definition for listener: ' . $name,
                    Response::HTTP_INTERNAL_SERVER_ERROR,
                );
            }
        }

        return $this;
    }

    public function getHandlerInstance($name)
    {
        if (is_array($this->eventHandlers[$name])) {
            // The listener has not been initialized
            $className = $this->eventHandlers[$name]['handler'];
            $params = $this->eventHandlers[$name]['params'];
            $handler = new $className($params ?: null);

            // Run initializers
            foreach ($this->initializers as $initializer) {
                $initializer->initialize($handler);
            }

            $this->eventHandlers[$name] = $handler;
        }

        return $this->eventHandlers[$name];
    }

    public function addInitializer(InitializerInterface $initializer): self
    {
        $this->initializers[] = $initializer;

        return $this;
    }

    /**
     * @return array<InitializerInterface>
     */
    public function getInitializers(): array
    {
        return $this->initializers;
    }

    public function trigger(string $eventName, array $params = []): EventManager
    {
        $event = clone $this->event;
        $event->setName($eventName);

        // Add optional extra arguments
        foreach ($params as $key => $value) {
            $event->setArgument($key, $value);
        }

        // Fetch current user
        $user = $event->getRequest()->getUser();

        // Trigger all listeners for this event and pass in the event instance
        foreach ($this->getListenersForEvent($eventName) as $listener) {
            $event->setArgument('handler', $listener['handler']);
            $callback = $this->getHandlerInstance($listener['handler']);

            if ($callback instanceof ListenerInterface) {
                $callback = [$callback, $listener['method']];
            }

            $users = $listener['users'];

            if (!$this->triggersFor($user, $users)) {
                continue;
            }

            $callback($event);

            if ($event->isPropagationStopped()) {
                break;
            }
        }

        return $this;
    }

    /**
     * Get all listeners that listens for an event, including wildcard listeners
     *
     * @return array<PriorityQueue>
     */
    private function getListenersForEvent(string $event): array
    {
        $listeners = [];

        foreach ($this->getEventNameParts($event) as $name) {
            if (isset($this->callbacks[$name])) {
                foreach (clone $this->callbacks[$name] as $listener) {
                    $listeners[] = $listener;
                }
            }
        }

        return $listeners;
    }

    /**
     * Get all parts of an event name
     *
     * @param array<string>
     */
    private function getEventNameParts(string $event): array
    {
        $parts = ['*'];
        $offset = 0;

        while ($offset = strpos($event, '.', $offset + 1)) {
            $parts[] = substr($event, 0, $offset) . '.*';
        }

        $parts[] = $event;

        return $parts;
    }

    public function hasListenersForEvent(string $eventName): bool
    {
        return !empty($this->callbacks[$eventName]);
    }

    public function setEventTemplate(EventInterface $event): self
    {
        $this->event = $event;
        return $this;
    }

    private function triggersFor(?string $user = null, array $filter = []): bool
    {
        if (empty($user) || empty($filter)) {
            return true;
        }

        $filter = array_merge(['whitelist' => [], 'blacklist' => []], $filter);

        $whitelist = array_flip($filter['whitelist']);
        $blacklist = array_flip($filter['blacklist']);

        if (
            // Both lists are empty
            empty($whitelist) && empty($blacklist) ||

            // Whitelist is empty, and the user is not blacklisted
            empty($whitelist) && !isset($blacklist[$user]) ||

            // Blacklist is empty, and the user is whitelisted
            empty($blacklist) && isset($whitelist[$user])
        ) {
            return true;
        }

        return false;
    }
}
