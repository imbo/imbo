<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\IntegrationTest\Cache;

use Imbo\Cache\APC;

/**
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Test suite\Integration tests
 */
class APCTest extends CacheTests {
    protected function getDriver() {
        if (!extension_loaded('apc')) {
            $this->markTestSkipped('APC is not installed');
        }

        if (!ini_get('apc.enable_cli')) {
            $this->markTestSkipped('apc.enable_cli must be set to On to run this test case');
        }

        return new APC('ImboTestSuite');
    }
}
