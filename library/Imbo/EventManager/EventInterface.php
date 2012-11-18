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
 * @package Interfaces
 * @subpackage EventManager
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
 * Event interface
 *
 * @package Interfaces
 * @subpackage EventManager
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
interface EventInterface {
    /**
     * Get the name of the event
     *
     * @return string
     */
    function getName();

    /**
     * Get the request instance
     *
     * @return RequestInterface
     */
    function getRequest();

    /**
     * Get the response instance
     *
     * @return ResponseInterface
     */
    function getResponse();

    /**
     * Get the database adapter
     *
     * @return DatabaseInterface
     */
    function getDatabase();

    /**
     * Get the storage adapter
     *
     * @return StorageInterface
     */
    function getStorage();

    /**
     * Get the event manager that triggered the event
     *
     * @return EventManagerInterface
     */
    function getManager();

    /**
     * Whether or not to stop the execution of more listeners for the current event
     *
     * @param boolean $flag True to stop, false to continue
     * @return EventInterface
     */
    function stopPropagation($flag);

    /**
     * Return whether or not the propagation should stop
     *
     * @return boolean
     */
    function propagationIsStopped();

    /**
     * Whether or not to halt the execution of Imbo
     *
     * @param boolean $flag True to halt, false to continue
     * @return EventInterface
     */
    function haltApplication($flag);

    /**
     * Return whether or not the execution should be halted
     *
     * @return boolean
     */
    function applicationIsHalted();

    /**
     * Return optional parameters passed to the event instance
     *
     * @return array
     */
    function getParams();
}
