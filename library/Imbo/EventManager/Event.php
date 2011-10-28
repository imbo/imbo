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

/**
 * Event
 *
 * @package Imbo
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */
class Event implements EventInterface {
    /**
     * Name of the event
     *
     * @var string
     */
    private $name;

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
     * Database instance
     *
     * @var Imbo\Database\DatabaseInterface
     */
    private $database;

    /**
     * Storage instance
     *
     * @var Imbo\Storage\StorageInterface
     */
    private $storage;

    /**
     * Class constructor
     *
     * @param string $name
     * @param Imbo\Http\Request\RequestInterface $request
     * @param Imbo\Http\Response\ResponseInterface $response
     * @param Imbo\Database\DatabaseInterface $database
     * @param Imbo\Storage\StorageInterface $storage
     */
    public function __construct($name, RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        $this->name = $name;
        $this->request  = $request;
        $this->response = $response;
        $this->database = $database;
        $this->storage  = $storage;
    }

    /**
     * @see Imbo\EventManager\EventInterface::getName()
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @see Imbo\EventManager\EventInterface::getRequest()
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * @see Imbo\EventManager\EventInterface::getResponse()
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * @see Imbo\EventManager\EventInterface::getDatabase()
     */
    public function getDatabase() {
        return $this->database;
    }

    /**
     * @see Imbo\EventManager\EventInterface::getStorage()
     */
    public function getStorage() {
        return $this->storage;
    }
}
