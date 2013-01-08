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

/**
 * APC cache
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Cache
 */
class APC implements CacheInterface {
    /**
     * Key namespace
     *
     * @var string
     */
    private $namespace;

    /**
     * Class constructor
     *
     * @param string $namespace A prefix that will be added to all keys
     */
    public function __construct($namespace = null) {
        $this->namespace = $namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key) {
        return apc_fetch($this->getKey($key));
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $expire = 0) {
        return apc_store($this->getKey($key), $value, $expire);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key) {
        return apc_delete($this->getKey($key));
    }

    /**
     * {@inheritdoc}
     */
    public function increment($key, $amount = 1) {
        return apc_inc($this->getKey($key), $amount);
    }

    /**
     * {@inheritdoc}
     */
    public function decrement($key, $amount = 1) {
        $result = apc_dec($this->getKey($key), $amount);

        if ($result < 0) {
            $result = 0;
            $this->set($key, $result);
        }

        return $result;
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
}
