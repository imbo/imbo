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

use Imbo\EventListener\ListenerInterface,
    Imbo\Container,
    Imbo\ContainerAware,
    Imbo\EventListener\PublicKeyAwareListenerInterface,
    Imbo\Exception\InvalidArgumentException,
    Imbo\Exception\RuntimeException,
    Imbo\Exception\HaltApplication,
    Imbo\Http\Request\RequestInterface,
    Imbo\Http\Response\ResponseInterface,
    Imbo\Database\DatabaseInterface,
    Imbo\Storage\StorageInterface,
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
class EventManager implements ContainerAware, EventManagerInterface {
    /**
     * Callbacks that can be triggered
     *
     * @var array
     */
    private $callbacks;

    /**
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
     * {@inheritdoc}
     */
    public function attach($events, $callback, $priority = 1) {
        if (!is_array($events)) {
            $events = array($events);
        }

        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Callback is not callable');
        }

        foreach ($events as $event) {
            if (empty($this->callbacks[$event])) {
                $this->callbacks[$event] = new SplPriorityQueue();
            }

            $this->callbacks[$event]->insert($callback, $priority);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function attachListener(ListenerInterface $listener, $priority = 1) {
        if ($listener instanceof PublicKeyAwareListenerInterface && !$listener->triggersFor($this->container->get('request')->getPublicKey())) {
            return $this;
        }

        return $this->attach($listener->getEvents(), function (EventInterface $event) use ($listener) {
            $eventName = $event->getName();

            $methodName = preg_replace_callback(
                "#(\.)([a-z]{1})#",
                function ($matches) {
                    return strtoupper($matches[2]);
                },
                $eventName
            );

            $methodName = 'on' . ucfirst($methodName);

            if (!method_exists($listener, $methodName)) {
                throw new RuntimeException(get_class($listener) . ' can not execute "' . $eventName . '"');
            }

            $listener->$methodName($event);
        }, $priority);
    }

    /**
     * {@inheritdoc}
     */
    public function trigger($eventName, array $params = array()) {
        if (!empty($this->callbacks[$eventName])) {
            // Fetch an event
            $event = $this->container->get('event', array(
                'name' => $eventName,
                'params' => $params,
            ));

            // Trigger all listeners for this event and pass in the event instance
            foreach ($this->callbacks[$eventName] as $callback) {
                $callback($event);

                if ($event->propagationIsStopped()) {
                    break;
                }
            }

            if ($event->applicationIsHalted()) {
                throw new HaltApplication();
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasListenersForEvent($eventName) {
        return !empty($this->callbacks[$eventName]);
    }
}
