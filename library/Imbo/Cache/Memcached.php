<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Cache;

use Memcached as PeclMemcached;

/**
 * Memcached cache
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Cache
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
     * @var PeclMemcached
     */
    private $memcached;

    /**
     * Class constructor
     *
     * @param PeclMemcached $memcached An instance of pecl/memcached
     * @param string $namespace A prefix that will be added to all keys
     */
    public function __construct(PeclMemcached $memcached, $namespace = null) {
        $this->memcached = $memcached;
        $this->namespace = $namespace;
    }

    /**
     * Generate a namespaced key
     *
     * @param string $key The key specified by the user
     * @return string A namespaced key
     */
    protected function getKey($key) {
        return $this->namespace . ':' . $key;
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
