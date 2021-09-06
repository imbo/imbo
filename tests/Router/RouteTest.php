<?php declare(strict_types=1);
namespace Imbo\Router;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Router\Route
 */
class RouteTest extends TestCase
{
    private Route $route;

    public function setUp(): void
    {
        $this->route = new Route();
    }

    /**
     * @covers ::__toString
     */
    public function testReturnsNullWhenNameIsNotSet(): void
    {
        $this->assertSame('', (string) $this->route);
    }

    /**
     * @covers ::setName
     * @covers ::__toString
     */
    public function testReturnsTheSetName(): void
    {
        $this->assertSame($this->route, $this->route->setName('name'));
        $this->assertSame('name', (string) $this->route);
    }
}
