<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\EventManager;

use Imbo\EventListener\ListenerInterface,
    Imbo\EventListener\Initializer\InitializerInterface,
    Imbo\Exception\InvalidArgumentException;

/**
 * Event manager
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Event\Manager
 */
class EventManager {
    /**
     * The event handlers
     *
     * @var array
     */
    private $eventHandlers = [];

    /**
     * Event template
     *
     * @var EventInterface
     */
    private $event;

    /**
     * Map of events and callbacks
     *
     * @var array
     */
    private $callbacks = [];

    /**
     * Event listener initializers
     *
     * @var InitializerInterface[]
     */
    private $initializers = [];

    /**
     * Register an event handler
     *
     * @param string $name The name of the handler
     * @param mixed $handler The handler itself
     * @param array $params Parameters for the handler if $handler is a string
     * @return self
     */
    public function addEventHandler($name, $handler, array $params = []) {
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
     *
     * @param string $name The name of the handler that owns the callback
     * @param array $events Which events the callback will trigger for
     * @param array $users User filter for the events
     * @return self
     */
    public function addCallbacks($name, array $events, array $users = []) {
        // Default priority
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
            } else if (is_array($callback)) {
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
            } else if (is_int($callback)) {
                // We have a closure as a callback, so $callback is the actual priority
                $this->callbacks[$event]->insert([
                    'handler' => $name,
                    'users' => $users,
                ], $callback);
            } else {
                throw new InvalidArgumentException('Invalid event definition for listener: ' . $name, 500);
            }
        }

        return $this;
    }

    /**
     * Get a handler instance
     *
     * @param string $name The name of the handler
     * @return ListenerInterface
     */
    public function getHandlerInstance($name) {
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

    /**
     * Add an event listener initializer
     *
     * @param InitializerInterface $initializer An initializer instance
     * @return self
     */
    public function addInitializer(InitializerInterface $initializer) {
        $this->initializers[] = $initializer;

        return $this;
    }

    /**
     * Trigger a given event
     *
     * @param string $eventName The name of the event to trigger
     * @param array $params Extra parameters for the event
     * @return EventManager
     */
    public function trigger($eventName, array $params = []) {
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
     * @param string $event Name of the event
     * @return PriorityQueue[]
     */
    private function getListenersForEvent($event) {
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
     * @param string $event
     * @param string[]
     */
    private function getEventNameParts($event) {
        $parts = ['*'];
        $offset = 0;

        while ($offset = strpos($event, '.', $offset + 1)) {
            $parts[] = substr($event, 0, $offset) . '.*';
        }

        $parts[] = $event;

        return $parts;
    }

    /**
     * Whether or not the manager has event listeners that subscribes to a specific event
     *
     * @param string $eventName The name of the event to check
     * @return boolean
     */
    public function hasListenersForEvent($eventName) {
        return !empty($this->callbacks[$eventName]);
    }

    /**
     * Set the event template
     *
     * This event instance will be cloned for each use of the trigger method
     *
     * @param EventInterface $event A configured event instance
     * @return self
     */
    public function setEventTemplate(EventInterface $event) {
        $this->event = $event;

        return $this;
    }

    /**
     * Check if a listener will trigger for a given user
     *
     * @param string $user The user to check for, can be null
     * @param array $filter The array from the listener with "whitelist" and "blacklist"
     * @return boolean
     */
    private function triggersFor($user = null, array $filter = []) {
        if (empty($user) || empty($filter)) {
            return true;
        }

        $filter = array_merge(['whitelist' => [], 'blacklist' => []],  $filter);

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
