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
 * @package EventManager
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
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
 * @package EventManager
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
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
     * @param array $params Optional extra parameters to send to the event listeners for the current
     *                      event
     * @return EventManager
     */
    public function trigger($eventName, array $params = array()) {
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

                $callback($event);

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
