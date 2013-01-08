<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\EventListener;

/**
 * Event listener definition
 *
 * @package EventListener
 * @author Christer Edvartsen <cogo@starzinger.net>
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
