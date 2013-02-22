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

use Imbo\Container,
    Imbo\ContainerAware,
    Imbo\EventListener\ListenerDefinition,
    Imbo\EventListener\ListenerInterface,
    Imbo\Exception\InvalidArgumentException,
    SplPriorityQueue;

/**
 * Event manager
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Event\Manager
 */
class EventManager implements ContainerAware {
    /**
     * Callbacks that can be triggered
     *
     * @var array
     */
    private $callbacks;

    /**
     * Service container
     *
     * @var Container
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(Container $container) {
        $this->container = $container;
    }

    /**
     * Attach a callable to an event
     *
     * @param string $eventName The event to attach to
     * @param callback $callback Code that will be called when the event is triggered
     * @param int $priority Priority of the callback
     * @param array $publicKeys Filter using "include" or "exclude"
     * @throws InvalidArgumentException
     * @return EventManager
     */
    public function attach($eventName, $callback, $priority = 1, $publicKeys = array()) {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Callback for event ' . $eventName . ' is not callable');
        }

        if (empty($this->callbacks[$eventName])) {
            $this->callbacks[$eventName] = new SplPriorityQueue();
        }

        $this->callbacks[$eventName]->insert(array(
            'callback' => $callback,
            'publicKeys' => $publicKeys,
        ), $priority);

        return $this;
    }

    /**
     * Attach a listener definition
     *
     * @param ListenerDefinition $definition An instance of a listener definition
     * @return EventManager
     */
    public function attachDefinition(ListenerDefinition $definition) {
        return $this->attach(
            $definition->getEventName(),
            $definition->getCallback(),
            $definition->getPriority(),
            $definition->getPublicKeys()
        );
    }

    /**
     * Attach a listener
     *
     * @param ListenerInterface $listener An instance of an event listener
     * @return EventManager
     */
    public function attachListener(ListenerInterface $listener) {
        foreach ($listener->getDefinition() as $definition) {
            $this->attachDefinition($definition);
        }

        return $this;
    }

    /**
     * Trigger a given event
     *
     * @param string $eventName The name of the event to trigger
     * @return EventManager
     */
    public function trigger($eventName) {
        if (!empty($this->callbacks[$eventName])) {
            // Fetch current public key
            $publicKey = $this->container->get('request')->getPublicKey();

            // Fetch and configure a new event
            $event = $this->container->get('event');
            $event->setName($eventName);

            // Trigger all listeners for this event and pass in the event instance
            foreach ($this->callbacks[$eventName] as $listener) {
                $callback = $listener['callback'];
                $publicKeys = $listener['publicKeys'];

                if (!$this->triggersFor($publicKey, $publicKeys)) {
                    continue;
                }

                call_user_func($callback, $event);

                if ($event->propagationIsStopped()) {
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
     * Check if a listener will trigger for a given public key
     *
     * @param string $publicKey The public key to check for, can be null
     * @param array $publicKeys The array from the listener with "include" and "exclude"
     * @return boolean
     */
    private function triggersFor($publicKey, array $publicKeys) {
        if (empty($publicKey) || empty($publicKeys)) {
            return true;
        }

        if (
            (isset($publicKeys['include']) && !in_array($publicKey, $publicKeys['include'])) ||
            (isset($publicKeys['exclude']) && in_array($publicKey, $publicKeys['exclude']))
        ) {
            return false;
        }

        return true;
    }
}
