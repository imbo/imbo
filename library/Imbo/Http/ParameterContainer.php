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
 * @package Http
 * @subpackage Containers
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\Http;

/**
 * Parameter container
 *
 * Instances of this container will usually hold paramters found in for instance the $_GET or
 * $_POST superglobals.
 *
 * @package Http
 * @subpackage Containers
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class ParameterContainer {
    /**
     * Parameters in the container
     *
     * @var array
     */
    protected $parameters;

    /**
     * Class constructor
     *
     * @param array $parameters Parameters to store in the container
     */
    public function __construct(array $parameters) {
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function getAll() {
        return $this->parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function removeAll() {
        $this->parameters = array();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value) {
        $this->parameters[$key] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null) {
        return isset($this->parameters[$key]) ? $this->parameters[$key] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key) {
        unset($this->parameters[$key]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function has($key) {
        return isset($this->parameters[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function asString() {
        // Translate %5B and %5D back to [] as the client uses [] when generating the access token
        return preg_replace('/%5B\d+%5D/', '[]', http_build_query($this->parameters));
    }
}
