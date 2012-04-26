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
 * @package Resources
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\Resource;

use Imbo\Http\Request\RequestInterface,
    Imbo\Http\Response\ResponseInterface,
    Imbo\Database\DatabaseInterface,
    Imbo\Storage\StorageInterface,
    Imbo\Resource\ResourceInterface,
    Imbo\Exception\ResourceException,
    Imbo\EventManager\EventManagerInterface,
    DateTime;

/**
 * Abstract resource class
 *
 * Resources can extend this class and override supported methods.
 *
 * @package Resources
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
abstract class Resource implements ResourceInterface {
    /**
     * Event manager
     *
     * @var Imbo\EventManager\EventManagerInterface
     */
    protected $eventManager;

    /**
     * @see Imbo\Resource\ResourceInterface::post()
     */
    public function post(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        throw new ResourceException('Method not allowed', 405);
    }

    /**
     * @see Imbo\Resource\ResourceInterface::get()
     */
    public function get(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        throw new ResourceException('Method not allowed', 405);
    }

    /**
     * @see Imbo\Resource\ResourceInterface::head()
     */
    public function head(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        throw new ResourceException('Method not allowed', 405);
    }

    /**
     * @see Imbo\Resource\ResourceInterface::delete()
     */
    public function delete(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        throw new ResourceException('Method not allowed', 405);
    }

    /**
     * @see Imbo\Resource\ResourceInterface::put()
     */
    public function put(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        throw new ResourceException('Method not allowed', 405);
    }

    /**
     * @see Imbo\Resource\ResourceInterface::setEventManager()
     */
    public function setEventManager(EventManagerInterface $eventManager) {
        $this->eventManager = $eventManager;

        return $this;
    }

    /**
     * Get a formatted date
     *
     * @param DateTime $date An instance of DateTime
     * @return string Returns a formatted date string
     */
    protected function formatDate(DateTime $date) {
        return $date->format('D, d M Y H:i:s') . ' GMT';
    }
}
