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

use Imbo\Cache\APC;

/**
 * @covers Imbo\Cache\APC
 * @group integration
 * @group cache
 */
class APCTest extends CacheTests {
    protected function getDriver() {
        if (!extension_loaded('apc') && !extension_loaded('apcu')) {
            $this->markTestSkipped('APC(u) is not installed');
        }

        if (!ini_get('apc.enable_cli')) {
            $this->markTestSkipped('apc.enable_cli must be set to On to run this test case');
        }

        return new APC('ImboTestSuite');
    }
}
