<?php
namespace ImboUnitTest;

use Imbo\Resource;
use PHPUnit\Framework\TestCase;

/**
 * @covers Imbo\Resource
 * @group unit
 */
class ResourceTest extends TestCase {
    public function testMethodsReturnsArrays() {
        $this->assertIsArray(Resource::getReadOnlyResources());
        $this->assertIsArray(Resource::getReadWriteResources());
        $this->assertIsArray(Resource::getAllResources());
    }
}
