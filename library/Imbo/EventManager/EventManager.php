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
    Imbo\Exception\InvalidArgumentException,
    ReflectionClass;

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
    private $eventHandlers = array();

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
    private $callbacks = array();

    /**
     * Event listener initializers
     *
     * @var InitializerInterface[]
     */
    private $initializers = array();

    /**
     * Register an event handler
     *
     * @param string $name The name of the handler
     * @param mixed $handler The handler itself
     * @param array $params Parameters for the handler if $handler is a string
     * @return self
     */
    public function addEventHandler($name, $handler, array $params = array()) {
        if (is_string($handler)) {
            $this->eventHandlers[$name] = array(
                'handler' => $handler,
                'params' => $params,
            );
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
     * @param array $publicKeys Public key filter for the events
     * @return self
     */
    public function addCallbacks($name, array $events, array $publicKeys = array()) {
        // Default priority
        $defaultPriority = 0;

        foreach ($events as $event => $callback) {
            if (!isset($this->callbacks[$event])) {
                // Create a priority queue for this event
                $this->callbacks[$event] = new PriorityQueue();
            }

            if (is_string($callback)) {
                // 'eventName' => 'someMethod'
                $this->callbacks[$event]->insert(array(
                    'handler' => $name,
                    'method' => $callback,
                    'publicKeys' => $publicKeys,
                ), $defaultPriority);
            } else if (is_array($callback)) {
                // 'eventName' => array( ... )
                foreach ($callback as $method => $priority) {
                    if (is_int($method)) {
                        // 'eventName' => array('someMethod', ...)
                        $method = $priority;
                        $priority = $defaultPriority;
                    }

                    $this->callbacks[$event]->insert(array(
                        'handler' => $name,
                        'method' => $method,
                        'publicKeys' => $publicKeys,
                    ), $priority);
                }
            } else if (is_int($callback)) {
                // We have a closure as a callback, so $callback is the actual priority
                $this->callbacks[$event]->insert(array(
                    'handler' => $name,
                    'publicKeys' => $publicKeys,
                ), $callback);
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

            if (empty($params)) {
                // No params
                $handler = new $className();
            } else {
                // Params, need to use reflection.
                // <ghetto>
                $reflection = new ReflectionClass($this->eventHandlers[$name]['handler']);
                $handler = $reflection->newInstanceArgs($params);
                // </ghetto>
            }

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
    public function trigger($eventName, array $params = array()) {
        if (!empty($this->callbacks[$eventName])) {
            $event = clone $this->event;
            $event->setName($eventName);

            // Add optional extra arguments
            foreach ($params as $key => $value) {
                $event->setArgument($key, $value);
            }

            // Fetch current public key
            $publicKey = $event->getRequest()->getPublicKey();

            // Trigger all listeners for this event and pass in the event instance
            foreach (clone $this->callbacks[$eventName] as $listener) {
                $event->setArgument('handler', $listener['handler']);
                $callback = $this->getHandlerInstance($listener['handler']);

                if ($callback instanceof ListenerInterface) {
                    $callback = array($callback, $listener['method']);
                }

                $publicKeys = $listener['publicKeys'];

                if (!$this->triggersFor($publicKey, $publicKeys)) {
                    continue;
                }

                $callback($event);

                if ($event->isPropagationStopped()) {
                    break;
                }
            }
        }

        return $this;
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
     * Check if a listener will trigger for a given public key
     *
     * @param string $publicKey The public key to check for, can be null
     * @param array $filter The array from the listener with "whitelist" and "blacklist"
     * @return boolean
     */
    private function triggersFor($publicKey = null, array $filter = array()) {
        if (empty($publicKey) || empty($filter)) {
            return true;
        }

        $filter = array_merge(array('whitelist' => array(), 'blacklist' => array()),  $filter);

        $whitelist = array_flip($filter['whitelist']);
        $blacklist = array_flip($filter['blacklist']);

        if (
            // Both lists are empty
            empty($whitelist) && empty($blacklist) ||

            // Whitelist is empty, and the public key is not blacklisted
            empty($whitelist) && !isset($blacklist[$publicKey]) ||

            // Blacklist is empty, and the public key is whitelisted
            empty($blacklist) && isset($whitelist[$publicKey])
        ) {
            return true;
        }

        return false;
    }
}
