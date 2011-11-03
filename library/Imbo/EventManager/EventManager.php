<?php
/**
 * Imbo
 *
 * Copyright (c) 2011 Christer Edvartsen <cogo@starzinger.net>
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
 * @package Imbo
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */

namespace Imbo\EventManager;

use Imbo\Http\Request\RequestInterface,
    Imbo\Http\Response\ResponseInterface,
    InvalidArgumentException,
    SplPriorityQueue;

/**
 * Event manager
 *
 * @package Imbo
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */
class EventManager implements EventManagerInterface {
    /**
     * Different events that can be triggerd
     *
     * @var array
     */
    private $events;

    /**
     * Request instance
     *
     * @var Imbo\Http\Request\RequestInterface
     */
    private $request;

    /**
     * Response instance
     *
     * @var Imbo\Http\Response\ResponseInterface
     */
    private $response;

    /**
     * Class constructor
     *
     * @param Imbo\Http\Request\RequestInterface $request
     * @param Imbo\Http\Response\ResponseInterface $response
     */
    public function __construct(RequestInterface $request, ResponseInterface $response) {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @see Imbo\EveneManager\EventManagerInterface::attach()
     */
    public function attach($events, $callback, $priority = 1) {
        if (!is_array($events)) {
            $events = array($events);
        }

        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Callback is not callable');
        }

        foreach ($events as $event) {
            if (empty($this->events[$event])) {
                $this->events[$event] = new SplPriorityQueue();
            }

            $this->events[$event]->insert($callback, $priority);
        }

        return $this;
    }

    /**
     * @see Imbo\EveneManager\EventManagerInterface::trigger()
     */
    public function trigger($event) {
        if (!empty($this->events[$event])) {
            // Create an event instance
            $e = new Event($event, $this->request, $this->response);

            // Trigger all listeners for this event and pass in the event instance
            foreach ($this->events[$event] as $callback) {
                $callback($e);
            }
        }

        return $this;
    }
}
