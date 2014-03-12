<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboIntegrationTest\Cache;

use Imbo\Cache\Memcached,
    Memcached as PeclMemcached;

/**
 * @covers Imbo\Cache\Memcached
 * @group integration
 * @group cache
 */
class MemcachedTest extends CacheTests {
    protected function getDriver() {
        if (!extension_loaded('memcached')) {
            $this->markTestSkipped('Memcached is not installed');
        }

        $host = !empty($GLOBALS['MEMCACHED_HOST']) ? $GLOBALS['MEMCACHED_HOST'] : null;
        $port = !empty($GLOBALS['MEMCACHED_PORT']) ? $GLOBALS['MEMCACHED_PORT'] : null;

        if (!$host || !$port) {
            $this->markTestSkipped('Specify both MEMCACHED_HOST and MEMCACHED_PORT in your phpunit.xml file to run this test case');
        }

        $memcached = new PeclMemcached();
        $memcached->addServer($host, $port);

        static $timestamp = 0;

        if (!$timestamp) {
            $timestamp = microtime(true);
        }

        return new Memcached($memcached, 'ImboTestSuite' . $timestamp);
    }
}
