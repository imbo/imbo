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

use InvalidArgumentException;
use SplPriorityQueue;

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
     * @see Imbo\EveneManager\EventManagerInterface::attach()
     */
    public function attach($eventName, $callback, $priority = 1) {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Callback is not callable');
        }

        if (empty($this->events[$eventName])) {
            $this->events[$eventName] = new SplPriorityQueue();
        }

        $this->events[$eventName]->insert($callback, $priority);

        return $this;
    }

    /**
     * @see Imbo\EveneManager\EventManagerInterface::trigger()
     */
    public function trigger($eventName) {
        if (!empty($this->events[$eventName])) {
            foreach ($this->events[$eventName] as $callback) {
                $callback();
            }
        }

        return $this;
    }
}
