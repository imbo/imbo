<?php
namespace ImboUnitTest;

use Imbo\Resource;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Resource
 */
class ResourceTest extends TestCase {
    public function testMethodsReturnsArrays() : void {
        $this->assertIsArray(Resource::getReadOnlyResources());
        $this->assertIsArray(Resource::getReadWriteResources());
        $this->assertIsArray(Resource::getAllResources());
    }
}
