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

use Imbo\Http\Request\RequestInterface,
    Imbo\Http\Response\ResponseInterface,
    Imbo\Database\DatabaseInterface,
    Imbo\Storage\StorageInterface;

/**
 * Event class
 *
 * @package EventManager
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class Event implements EventInterface {
    /**
     * Name of the current event
     *
     * @var string
     */
    private $name;

    /**
     * Request instance
     *
     * @var RequestInterface
     */
    private $request;

    /**
     * Response instance
     *
     * @var ResponseInterface
     */
    private $response;

    /**
     * The database adapter
     *
     * @var DatabaseInterface
     */
    private $database;

    /**
     * The storage adapter
     *
     * @var StorageInterface
     */
    private $storage;

    /**
     * The event manager
     *
     * @var EventManagerInterface
     */
    private $eventManager;

    /**
     * Imbo configuration
     *
     * @var array
     */
    private $config;

    /**
     * Propagation flag
     *
     * @var boolean
     */
    private $propagationIsStopped = false;

    /**
     * Execution flag
     *
     * @var boolean
     */
    private $applicationIsHalted = false;

    /**
     * Optional parameters
     *
     * @var array
     */
    private $params;

    /**
     * Class contsructor
     *
     * @param string $name The name of the current event
     * @param RequestInterface $request Request instance
     * @param ResponseInterface $response Response instance
     * @param DatabaseInterface $database Database instance
     * @param StorageInterface $storage Storage instance
     * @param EventManagerInterface $eventManager The event manager instance
     * @param array $config Imbo configuration
     * @param array $params Optional parameters
     */
    public function __construct(
        $name, RequestInterface $request, ResponseInterface $response, DatabaseInterface $database,
        StorageInterface $storage, EventManagerInterface $eventManager, array $config,
        array $params = array()
    ) {
        $this->name = $name;
        $this->request = $request;
        $this->response = $response;
        $this->database = $database;
        $this->storage = $storage;
        $this->eventManager = $eventManager;
        $this->config = $config;
        $this->params = $params;
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
        return $this->request;
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
    public function getDatabase() {
        return $this->database;
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
    public function getManager() {
        return $this->eventManager;
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
    public function haltApplication($flag) {
        $this->applicationIsHalted = $flag;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function applicationIsHalted() {
        return $this->applicationIsHalted;
    }

    /**
     * {@inheritdoc}
     */
    public function getParams() {
        return $this->params;
    }
}
