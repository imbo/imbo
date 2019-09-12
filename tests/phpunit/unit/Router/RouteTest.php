<?php
namespace ImboUnitTest\Router;

use Imbo\Router\Route;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Imbo\Router\Route
 */
class RouteTest extends TestCase {
    /**
     * @var Route
     */
    private $route;

    /**
     * Set up the route instance
     */
    public function setUp() : void {
        $this->route = new Route();
    }

    /**
     * @covers Imbo\Router\Route::__toString
     */
    public function testReturnsNullWhenNameIsNotSet() {
        $this->assertSame('', (string) $this->route);
    }

    /**
     * @covers Imbo\Router\Route::setName
     * @covers Imbo\Router\Route::__toString
     */
    public function testReturnsTheSetName() {
        $this->assertSame($this->route, $this->route->setName('name'));
        $this->assertSame('name', (string) $this->route);
    }
}
