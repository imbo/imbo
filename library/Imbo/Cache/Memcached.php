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
 * @package Cache
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\Cache;

use Memcached as PeclMemcached;

/**
 * Memcached cache
 *
 * @package Cache
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class Memcached implements CacheInterface {
    /**
     * Key namespace
     *
     * @var string
     */
    private $namespace;

    /**
     * The memcached instance to use
     *
     * @var \Memcached
     */
    private $memcached;

    /**
     * Class constructor
     *
     * @param string $namespace A prefix that will be added to all keys
     * @param \Memcached $memcached An instance of pecl/memcached
     */
    public function __construct($namespace = null, PeclMemcached $memcached) {
        $this->namespace = $namespace;
        $this->memcached = $memcached;
    }

    /**
     * Generate a namespaced key
     *
     * @param string $key The key specified by the user
     * @return string A namespaced key
     */
    protected function getKey($key) {
        return $this->namespace . $key;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key) {
        return $this->memcached->get($this->getKey($key));
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $expire = 0) {
        return $this->memcached->set($this->getKey($key), $value, $expire);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key) {
        return $this->memcached->delete($this->getKey($key));
    }

    /**
     * {@inheritdoc}
     */
    public function increment($key, $amount = 1) {
        return $this->memcached->increment($this->getKey($key), $amount);
    }

    /**
     * {@inheritdoc}
     */
    public function decrement($key, $amount = 1) {
        return $this->memcached->decrement($this->getKey($key), $amount);
    }
}
