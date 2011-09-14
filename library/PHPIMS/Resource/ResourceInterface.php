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
 * @subpackage Interfaces
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Resource;

use PHPIMS\Http\Request\RequestInterface;
use PHPIMS\Http\Response\ResponseInterface;
use PHPIMS\Database\DatabaseInterface;
use PHPIMS\Storage\StorageInterface;
use PHPIMS\Resource\Plugin\PluginInterface;

/**
 * Resource interface
 *
 * Available resources must implement this interface. They can also extend the abstract resource
 * class (PHPIMS\Resource\Resource) for convenience.
 *
 * @package PHPIMS
 * @subpackage Interfaces
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
interface ResourceInterface {
    /**#@+
     * State constants
     *
     * @var string
     */
    const STATE_PRE  = 'pre';
    const STATE_POST = 'post';
    /**#@-*/

    /**
     * Return an array with the allowed (implemented) HTTP methods for the current resource
     *
     * @return string[]
     */
    function getAllowedMethods();

    /**
     * Register a plugin
     *
     * @param string $state One of the state constants in this interface
     * @param string $method The HTTP method to attach the plugin to. Should be one of the METHOD_*
     *                       constants in PHPIMS\Http\Request\RequestInterface
     * @param int $index The index of the plugin
     * @param PHPIMS\Resource\Plugin\PluginInterface $plugin The plugin itself
     * @return PHPIMS\Resource\ResourceInterface
     */
    function registerPlugin($state, $method, $index, PluginInterface $plugin);

    /**
     * Get plugins that will execute *before* the resource executes its logic
     *
     * @param string $method The HTTP method to attach the plugin to. Should be one of the METHOD_*
     *                       constants in PHPIMS\Http\Request\RequestInterface
     * @return PHPIMS\Resource\Plugin\PluginInterface[]
     */
    function getPreExecPlugins($method);

    /**
     * Get plugins that will execute *after* the resource executes its logic
     *
     * @param string $method The HTTP method to attach the plugin to. Should be one of the METHOD_*
     *                       constants in PHPIMS\Http\Request\RequestInterface
     * @return PHPIMS\Resource\Plugin\PluginInterface[]
     */
    function getPostExecPlugins($method);

    /**
     * POST handler
     *
     * @param PHPIMS\Http\Request\RequestInterface   $request  A request instance
     * @param PHPIMS\Http\Response\ResponseInterface $response A response instance
     * @param PHPIMS\Database\DatabaseInterface $database A database instance
     * @param PHPIMS\Storage\StorageInterface   $storage  A storage instance
     * @throws PHPIMS\Resource\Exception
     */
    function post(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage);

    /**
     * GET handler
     *
     * @param PHPIMS\Http\Request\RequestInterface   $request  A request instance
     * @param PHPIMS\Http\Response\ResponseInterface $response A response instance
     * @param PHPIMS\Database\DatabaseInterface $database A database instance
     * @param PHPIMS\Storage\StorageInterface   $storage  A storage instance
     * @throws PHPIMS\Resource\Exception
     */
    function get(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage);

    /**
     * HEAD handler
     *
     * @param PHPIMS\Http\Request\RequestInterface   $request  A request instance
     * @param PHPIMS\Http\Response\ResponseInterface $response A response instance
     * @param PHPIMS\Database\DatabaseInterface $database A database instance
     * @param PHPIMS\Storage\StorageInterface   $storage  A storage instance
     * @throws PHPIMS\Resource\Exception
     */
    function head(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage);

    /**
     * DELETE handler
     *
     * @param PHPIMS\Http\Request\RequestInterface   $request  A request instance
     * @param PHPIMS\Http\Response\ResponseInterface $response A response instance
     * @param PHPIMS\Database\DatabaseInterface $database A database instance
     * @param PHPIMS\Storage\StorageInterface   $storage  A storage instance
     * @throws PHPIMS\Resource\Exception
     */
    function delete(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage);

    /**
     * OPTIONS handler
     *
     * @param PHPIMS\Http\Request\RequestInterface   $request  A request instance
     * @param PHPIMS\Http\Response\ResponseInterface $response A response instance
     * @param PHPIMS\Database\DatabaseInterface $database A database instance
     * @param PHPIMS\Storage\StorageInterface   $storage  A storage instance
     * @throws PHPIMS\Resource\Exception
     */
    function options(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage);

    /**
     * PUT handler
     *
     * @param PHPIMS\Http\Request\RequestInterface   $request  A request instance
     * @param PHPIMS\Http\Response\ResponseInterface $response A response instance
     * @param PHPIMS\Database\DatabaseInterface $database A database instance
     * @param PHPIMS\Storage\StorageInterface   $storage  A storage instance
     * @throws PHPIMS\Resource\Exception
     */
    function put(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage);
}
