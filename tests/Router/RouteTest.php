<?php declare(strict_types=1);
namespace Imbo\Router;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Route::class)]
class RouteTest extends TestCase
{
    private Route $route;

    public function setUp(): void
    {
        $this->route = new Route();
    }

    public function testReturnsNullWhenNameIsNotSet(): void
    {
        $this->assertSame('', (string) $this->route);
    }

    public function testReturnsTheSetName(): void
    {
        $this->assertSame($this->route, $this->route->setName('name'));
        $this->assertSame('name', (string) $this->route);
    }
}
