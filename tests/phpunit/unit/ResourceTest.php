<?php declare(strict_types=1);
namespace Imbo;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Resource
 */
class ResourceTest extends TestCase {
    /**
     * @covers ::getReadOnlyResources
     * @covers ::getReadWriteResources
     * @covers ::getAllResources
     */
    public function testMethodsReturnsArrays() : void {
        $this->assertIsArray(Resource::getReadOnlyResources());
        $this->assertIsArray(Resource::getReadWriteResources());
        $this->assertIsArray(Resource::getAllResources());
    }
}
