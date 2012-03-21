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
 * @subpackage Http\Containers
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\Http;

/**
 * Parameter container interface
 *
 * @package Interfaces
 * @subpackage Http\Containers
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
interface ParameterContainerInterface {
    /**
     * Get all parameters as an associative array
     *
     * @return array
     */
    function getAll();

    /**
     * Remove all parameters
     *
     * @return Imbo\Http\ParameterContainerInterface
     */
    function removeAll();

    /**
     * Set a parameter value
     *
     * @param string $key The key to store the value to
     * @param mixed $value The value itself
     * @return Imbo\Http\ParameterContainerInterface
     */
    function set($key, $value);

    /**
     * Get a parameter value
     *
     * @param string $key The key to fetch
     * @param mixed $default If the key does not exist, return this value instead
     * @return mixed
     */
    function get($key, $default = null);

    /**
     * Remove a single value from the parameter list
     *
     * @param string $key The key to remove
     * @return Imbo\Http\ParameterContainerInterface
     */
    function remove($key);

    /**
     * See if the container has a given key
     *
     * @param string $key The key to check for
     * @return boolean
     */
    function has($key);

    /**
     * Return the query as a string
     *
     * @return string
     */
    function asString();
}
