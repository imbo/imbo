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
    Imbo\ContainerAware;

/**
 * Event class
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Event
 */
class Event implements ContainerAware, EventInterface {
    /**
     * Name of the current event
     *
     * @var string
     */
    private $name;

    /**
     * Container instance
     *
     * @var Container
     */
    private $container;

    /**
     * Propagation flag
     *
     * @var boolean
     */
    private $propagationIsStopped = false;

    /**
     * Class contsructor
     *
     * @param string $name The name of the current event
     */
    public function __construct($name = null) {
        if ($name !== null) {
            $this->setName($name);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(Container $container) {
        $this->container = $container;
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
    public function getName() {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequest() {
        return $this->container->get('request');
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse() {
        return $this->container->get('response');
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabase() {
        return $this->container->get('database');
    }

    /**
     * {@inheritdoc}
     */
    public function getStorage() {
        return $this->container->get('storage');
    }

    /**
     * {@inheritdoc}
     */
    public function getManager() {
        return $this->container->get('eventManager');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig() {
        return $this->container->get('config');
    }

    /**
     * {@inheritdoc}
     */
    public function stopPropagation($flag) {
        $this->propagationIsStopped = $flag;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function propagationIsStopped() {
        return $this->propagationIsStopped;
    }
}
