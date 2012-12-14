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
 * @package EventListener
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\EventListener;

/**
 * Event listener definition
 *
 * @package EventListener
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class ListenerDefinition {
    /**
     * Event name
     *
     * @var string
     */
    private $eventName;

    /**
     * The callback
     *
     * @var callable
     */
    private $callback;

    /**
     * Priority if the callback
     *
     * @var int
     */
    private $priority;

    /**
     * Public key filter
     *
     * @var array
     */
    private $publicKeys;

    /**
     * Class constructor
     *
     * @param string $eventName Name of the event
     * @param callabla $callback Callable piece of code
     * @param int $priority The priority of the listener
     * @param array $publicKeys Public key filter
     */
    public function __construct($eventName, $callback, $priority = 1, $publicKeys = array()) {
        $this->setEventName($eventName);
        $this->setCallback($callback);
        $this->setPriority($priority);
        $this->setPublicKeys($publicKeys);
    }

    /**
     * Set the event name
     *
     * @param string $name
     * @return ListenerDefinition
     */
    public function setEventName($name) {
        $this->eventName = $name;

        return $this;
    }

    /**
     * Get the event name
     *
     * @return string
     */
    public function getEventName() {
        return $this->eventName;
    }

    /**
     * Set the callback
     *
     * @param callable $callback
     * @return ListenerDefinition
     */
    public function setCallback($callback) {
        $this->callback = $callback;

        return $this;
    }

    /**
     * Get the callback
     *
     * @return callable
     */
    public function getCallback() {
        return $this->callback;
    }

    /**
     * Set the priority
     *
     * @param int $priority
     * @return ListenerDefinition
     */
    public function setPriority($priority = 1) {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Get the priority
     *
     * @return int
     */
    public function getPriority() {
        return $this->priority;
    }

    /**
     * Set the public key filter
     *
     * @param array $publicKeys
     * @return ListenerDefinition
     */
    public function setPublicKeys(array $publicKeys) {
        $this->publicKeys = $publicKeys;

        return $this;
    }

    /**
     * Get the public key filter
     *
     * @return array
     */
    public function getPublicKeys() {
        return $this->publicKeys;
    }
}
