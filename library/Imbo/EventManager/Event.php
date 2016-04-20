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

use InvalidArgumentException;

/**
 * Event class
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Event
 */
class Event implements EventInterface {
    /**
     * @var string
     */
    private $name;

    /**
     * @var boolean
     */
    private $propagationStopped = false;

    /**
     * @var array
     */
    private $arguments = [];

    /**
     * Class constructor
     *
     * @param array $arguments
     */
    public function __construct(array $arguments = []) {
        $this->arguments = $arguments;
    }

    /**
     * Get the event name
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Sets the event name
     *
     * @param string $name
     * @return self
     */
    public function setName($name) {
        $this->name = $name;

        return $this;
    }

    /**
     * Check if propagation has been stopped
     *
     * @return boolean
     */
    public function isPropagationStopped() {
        return $this->propagationStopped;
    }

    /**
     * Stops the propagation of the event
     */
    public function stopPropagation() {
        $this->propagationStopped = true;

        return $this;
    }

    /**
     * Get argument
     *
     * @param string $key
     * @throws InvalidArgumentException
     * @return mixed
     */
    public function getArgument($key) {
        if ($this->hasArgument($key)) {
            return $this->arguments[$key];
        }

        throw new InvalidArgumentException(sprintf('Argument "%s" does not exist', $key), 500);
    }

    /**
     * Add argument
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function setArgument($key, $value) {
        $this->arguments[$key] = $value;

        return $this;
    }

    /**
     * Set arguments
     *
     * @param array $arguments
     * @return self
     */
    public function setArguments(array $arguments = []) {
        $this->arguments = $arguments;

        return $this;
    }

    /**
     * See if the event has an argument
     *
     * @param string $key
     * @return boolean
     */
    public function hasArgument($key) {
        return isset($this->arguments[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function getRequest() {
        return $this->getArgument('request');
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse() {
        return $this->getArgument('response');
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabase() {
        return $this->getArgument('database');
    }

    /**
     * {@inheritdoc}
     */
    public function getStorage() {
        return $this->getArgument('storage');
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessControl() {
        return $this->getArgument('accessControl');
    }

    /**
     * {@inheritdoc}
     */
    public function getManager() {
        return $this->getArgument('manager');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig() {
        return $this->getArgument('config');
    }

    /**
     * {@inheritdoc}
     */
    public function getHandler() {
        return $this->getArgument('handler');
    }
}
