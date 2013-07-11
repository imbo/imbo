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
 * Cache adapter interface
 *
 * An interface for cache adapters.
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Cache
 */
interface CacheInterface {
    /**
     * Get a cached value by a key
     *
     * @param string $key The key to get
     * @return mixed Returns the cached value or null if key does not exist
     */
    function get($key);

    /**
     * Store a value in the cache
     *
     * @param string $key The key to associate with the item
     * @param mixed $value The value to store
     * @param int $expire Number of seconds to keep the item in the cache
     * @return boolean True on success, false otherwise
     */
    function set($key, $value, $expire = 0);

    /**
     * Delete an item from the cache
     *
     * @param string $key The key to remove
     * @return boolean True on success, false otherwise
     */
    function delete($key);

    /**
     * Increment a value
     *
     * @param string $key The key to use
     * @param int $amount The amount to increment with
     * @return int|boolean Returns new value on success or false on failure
     */
    function increment($key, $amount = 1);

    /**
     * Decrement a value
     *
     * @param string $key The key to use
     * @param int $amount The amount to decrement with
     * @return int|boolean Returns new value on success or false on failure
     */
    function decrement($key, $amount = 1);
}
