<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace ImboUnitTest\Router;

use Imbo\Router\Route;

/**
 * @covers Imbo\Router\Route
 * @group unit
 * @group router
 */
class RouteTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Route
     */
    private $route;

    /**
     * Set up the route instance
     */
    public function setUp() {
        $this->route = new Route();
    }

    /**
     * Tear down the router instance
     */
    public function tearDown() {
        $this->route = null;
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
