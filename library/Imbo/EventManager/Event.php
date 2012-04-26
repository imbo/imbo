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
    Imbo\Storage\StorageInterface,
    Imbo\Image\ImageInterface;

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
     * Database driver
     *
     * @var Imbo\Database\DatabaseInterface
     */
    private $database;

    /**
     * Storage driver
     *
     * @var Imbo\Storage\StorageInterface
     */
    private $storage;

    /**
     * Image instance
     *
     * @var Imbo\Image\ImageInterface
     */
    private $image;

    /**
     * Class contsructor
     *
     * @param string $name The name of the current event
     * @param Imbo\Http\Request\RequestInterface $request Request instance
     * @param Imbo\Http\Response\ResponseInterface $response Response instance
     * @param Imbo\Database\DatabaseInterface $database Database driver
     * @param Imbo\Storage\StorageInterface $storage Storage driver
     * @param Imbo\Image\ImageInterface $image Image instance
     */
    public function __construct($name, RequestInterface $request, ResponseInterface $response,
                                DatabaseInterface $database, StorageInterface $storage,
                                ImageInterface $image = null) {
        $this->name     = $name;
        $this->request  = $request;
        $this->response = $response;
        $this->database = $database;
        $this->storage  = $storage;
        $this->image    = $image;
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

    /**
     * @see Imbo\EventManager\EventInterface::getImage()
     */
    public function getImage() {
        return $this->image;
    }
}
