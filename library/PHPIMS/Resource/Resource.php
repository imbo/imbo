<?php
/**
 * PHPIMS
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
 * @package PHPIMS
 * @subpackage Resources
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Resource;

use PHPIMS\Request\RequestInterface;
use PHPIMS\Response\ResponseInterface;
use PHPIMS\Database\DatabaseInterface;
use PHPIMS\Storage\StorageInterface;
use PHPIMS\Resource\Plugin\PluginInterface;
use PHPIMS\Resource\ResourceInterface;
use PHPIMS\Exception;

/**
 * Abstract resource class
 *
 * Resources can extend this class and override supported methods.
 *
 * @package PHPIMS
 * @subpackage Resources
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
abstract class Resource {
    /**
     * Plugins for the current resource
     *
     * @var array
     */
    private $plugins = array(
        ResourceInterface::STATE_PRE => array(
            RequestInterface::METHOD_POST    => array(),
            RequestInterface::METHOD_GET     => array(),
            RequestInterface::METHOD_HEAD    => array(),
            RequestInterface::METHOD_DELETE  => array(),
            RequestInterface::METHOD_OPTIONS => array(),
            RequestInterface::METHOD_PUT     => array(),
        ),
        ResourceInterface::STATE_POST => array(
            RequestInterface::METHOD_POST    => array(),
            RequestInterface::METHOD_GET     => array(),
            RequestInterface::METHOD_HEAD    => array(),
            RequestInterface::METHOD_DELETE  => array(),
            RequestInterface::METHOD_OPTIONS => array(),
            RequestInterface::METHOD_PUT     => array(),
        ),
    );

    /**
     * @see PHPIMS\Resource\ResourceInterface::registerPlugin()
     */
    public function registerPlugin($state, $method, $index, PluginInterface $plugin) {
        $this->plugins[$state][$method][$index] = $plugin;

        return $this;
    }

    /**
     * @see PHPIMS\Resource\ResourceInterface::getPreExecPlugins()
     */
    public function getPreExecPlugins($method) {
        return $this->getPlugins(ResourceInterface::STATE_PRE, $method);
    }

    /**
     * @see PHPIMS\Resource\ResourceInterface::getPostExecPlugins()
     */
    public function getPostExecPlugins($method) {
        return $this->getPlugins(ResourceInterface::STATE_POST, $method);
    }

    /**
     * Fetch plugins for a given method in a given state
     *
     * @param int $state One of the state constants in PHPIMS\Resource\Plugin\PluginInterface
     * @param int $method The HTTP method to attach the plugin to. Should be one of the METHOD_*
     *                    constants in PHPIMS\Request\RequestInterface
     * @return PHPIMS\Resource\Plugin\PluginInterface[]
     */
    private function getPlugins($state, $method) {
        return $this->plugins[$state][$method];
    }

    /**
     * @see PHPIMS\Resource\ResourceInterface::post()
     */
    public function post(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        throw new Exception('Method not allowed', 405);
    }

    /**
     * @see PHPIMS\Resource\ResourceInterface::get()
     */
    public function get(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        throw new Exception('Method not allowed', 405);
    }

    /**
     * @see PHPIMS\Resource\ResourceInterface::head()
     */
    public function head(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        throw new Exception('Method not allowed', 405);
    }

    /**
     * @see PHPIMS\Resource\ResourceInterface::delete()
     */
    public function delete(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        throw new Exception('Method not allowed', 405);
    }

    /**
     * @see PHPIMS\Resource\ResourceInterface::options()
     */
    public function options(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        throw new Exception('Method not allowed', 405);
    }

    /**
     * @see PHPIMS\Resource\ResourceInterface::put()
     */
    public function put(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        throw new Exception('Method not allowed', 405);
    }
}
