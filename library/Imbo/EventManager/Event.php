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

use Imbo\Http\Request\Request,
    Imbo\Http\Response\Response,
    Imbo\Database\DatabaseInterface,
    Imbo\Storage\StorageInterface;

/**
 * Event class
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Event
 */
class Event implements EventInterface {
    /**
     * Name of the current event
     *
     * @var string
     */
    private $name;

    /**
     * Current request
     *
     * @var Request
     */
    private $request;

    /**
     * Current response
     *
     * @var Response
     */
    private $response;

    /**
     * Datebase adapter
     *
     * @var DatabaseInterface
     */
    private $database;

    /**
     * Storage adapter
     *
     * @var StorageInterface
     */
    private $storage;

    /**
     * Configuration
     *
     * @var array
     */
    private $config;

    /**
     * The eventmanager
     *
     * @var EventManager
     */
    private $manager;

    /**
     * Propagation flag
     *
     * @var boolean
     */
    private $propagationIsStopped = false;

    /**
     * The handler for the current event
     *
     * @var string
     */
    private $handler;

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
    public function setRequest(Request $request) {
        $this->request = $request;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * {@inheritdoc}
     */
    public function setResponse(Response $response) {
        $this->response = $response;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * {@inheritdoc}
     */
    public function setDatabase(DatabaseInterface $database) {
        $this->database = $database;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabase() {
        return $this->database;
    }

    /**
     * {@inheritdoc}
     */
    public function setStorage(StorageInterface $storage) {
        $this->storage = $storage;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getStorage() {
        return $this->storage;
    }

    /**
     * {@inheritdoc}
     */
    public function setManager(EventManager $manager) {
        $this->manager = $manager;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getManager() {
        return $this->manager;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfig(array $config) {
        $this->config = $config;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig() {
        return $this->config;
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

    /**
     * {@inheritdoc}
     */
    public function setHandler($handler) {
        $this->handler = $handler;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getHandler() {
        return $this->handler;
    }
}
