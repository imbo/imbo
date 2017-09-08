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
     * {@inheritdoc}
     */
    public function getName() {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name) {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isPropagationStopped() {
        return $this->propagationStopped;
    }

    /**
     * {@inheritdoc}
     */
    public function stopPropagation() {
        $this->propagationStopped = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getArgument($key) {
        if ($this->hasArgument($key)) {
            return $this->arguments[$key];
        }

        throw new InvalidArgumentException(sprintf('Argument "%s" does not exist', $key), 500);
    }

    /**
     * {@inheritdoc}
     */
    public function setArgument($key, $value) {
        $this->arguments[$key] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments = []) {
        $this->arguments = $arguments;

        return $this;
    }

    /**
     * {@inheritdoc}
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
    public function getTransformationManager() {
        return $this->getArgument('transformationManager');
    }

    /**
     * {@inheritdoc}
     */
    public function getLoaderManager() {
        return $this->getArgument('loaderManager');
    }

    /**
     * {@inheritdoc}
     */
    public function getOutputConverterManager() {
        return $this->getArgument('outputConverterManager');
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
