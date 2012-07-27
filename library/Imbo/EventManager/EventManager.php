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
    Imbo\EventListener\ListenerInterface,
    Imbo\Exception\InvalidArgumentException;

/**
 * Event manager
 *
 * @package EventManager
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class EventManager implements EventManagerInterface {
    /**
     * Different events that can be triggerd
     *
     * @var array
     */
    private $events;

    /**
     * Container instance
     *
     * @var Container
     */
    private $container;

    /**
     * Class constructor
     *
     * @param Container $container Instance of a container
     */
    public function __construct(Container $container) {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function attach($events, $callback) {
        if (!is_array($events)) {
            $events = array($events);
        }

        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Callback is not callable');
        }

        foreach ($events as $event) {
            if (empty($this->events[$event])) {
                $this->events[$event] = array();
            }

            $this->events[$event][] = $callback;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function attachListener(ListenerInterface $listener) {
        // Fetch current public key
        $publicKey = $this->container->get('request')->getPublicKey();

        // Fetch the keys the listener is to be triggered for
        $keys = $listener->getPublicKeys();

        if (empty($keys) || in_array($publicKey, $keys)) {
            // Either no keys have been specified, or the listener wants to trigger for the current
            // key
            return $this->attach($listener->getEvents(), function(EventInterface $event) use($listener) {
                $listener->invoke($event);
            });
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function trigger($event) {
        if (!empty($this->events[$event])) {
            // Create an event instance
            $e = new Event($event, $this->container);

            // Trigger all listeners for this event and pass in the event instance
            foreach ($this->events[$event] as $callback) {
                $callback($e);
            }
        }

        return $this;
    }
}
