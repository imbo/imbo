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
 * @package TestSuite\IntegrationTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\IntegrationTest\Cache;

use Imbo\Cache\Memcached,
    Memcached as PeclMemcached;

/**
 * @package TestSuite\IntegrationTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\Cache\Memcached
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
