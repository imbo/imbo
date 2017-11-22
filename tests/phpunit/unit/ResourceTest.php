<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest;

use Imbo\Resource;
use PHPUnit\Framework\TestCase;

/**
 * @covers Imbo\Resource
 * @group unit
 */
class ResourceTest extends TestCase {
    public function testMethodsReturnsArrays() {
        $this->assertInternalType('array', Resource::getReadOnlyResources());
        $this->assertInternalType('array', Resource::getReadWriteResources());
        $this->assertInternalType('array', Resource::getAllResources());
    }
}
