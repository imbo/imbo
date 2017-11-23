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

use Imbo\Router;
use Imbo\Exception\RuntimeException;
use PHPUnit\Framework\TestCase;

/**
 * @covers Imbo\Router
 * @group unit
 * @group router
 */
class RouterTest extends TestCase {
    /**
     * @var Router
     */
    private $router;

    private $request;

    /**
     * Set up the router instance
     */
    public function setUp() {
        $this->router = new Router();
        $this->request = $this->getMockBuilder('Imbo\Http\Request\Request')
                              ->setMethods(['getPathInfo', 'getMethod'])
                              ->getMock();
    }

    /**
     * @covers Imbo\Router::route
     */
    public function testCanBeATeaPot() {
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('BREW'));
        $this->expectExceptionObject(new RuntimeException('I\'m a teapot', 418));
        $this->router->route($this->request);
    }

    /**
     * @covers Imbo\Router::route
     */
    public function testThrowsExceptionOnUnsupportedHttpMethod() {
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('TRACE'));
        $this->expectExceptionObject(new RuntimeException('Unsupported HTTP method', 501));
        $this->router->route($this->request);
    }

    /**
     * Return invalid routes for the resolve method
     *
     * @return array[]
     */
    public function getInvalidRoutes() {
        return [
            ['/foobar'],
            ['/status.json/'],
            ['/users/Christer'],
            ['/users/christer.json/'],
            ['/users/Christer.json/'],
            ['/users/christer/images.json/'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c.gif/'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/meta.json/'],
            ['/s/asdfghjk'],
            ['/s/asdfghj.jpg'],
        ];
    }

    /**
     * @dataProvider getInvalidRoutes
     * @covers Imbo\Router::route
     */
    public function testThrowsExceptionWhenNoRouteMatches($route) {
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $this->request->expects($this->once())->method('getPathInfo')->will($this->returnValue($route));
        $this->expectExceptionObject(new RuntimeException('Not Found', 404));
        $this->router->route($this->request);
    }

    /**
     * Returns valid routes for the router
     *
     * @return array[]
     */
    public function getValidRoutes() {
        return [
            // Global short URL resource
            ['/s/asdfghj', 'globalshorturl'],
            ['/s/1234567', 'globalshorturl'],
            ['/s/1234asd', 'globalshorturl'],
            ['/s/aAbB012', 'globalshorturl'],

            // Short URLs
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/shorturls', 'shorturls', 'christer', 'a9b80ed42957fd508c617549cad07d6c'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/shorturls/', 'shorturls', 'christer', 'a9b80ed42957fd508c617549cad07d6c'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/shorturls.json', 'shorturls', 'christer', 'a9b80ed42957fd508c617549cad07d6c', 'json'],

            // Short URL
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/shorturls/asdfghj', 'shorturl', 'christer', 'a9b80ed42957fd508c617549cad07d6c'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/shorturls/1234567', 'shorturl', 'christer', 'a9b80ed42957fd508c617549cad07d6c'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/shorturls/1234asd', 'shorturl', 'christer', 'a9b80ed42957fd508c617549cad07d6c'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/shorturls/aAbB012', 'shorturl', 'christer', 'a9b80ed42957fd508c617549cad07d6c'],

            // Status resource
            ['/status', 'status'],
            ['/status/', 'status'],
            ['/status.json', 'status', null, null, 'json'],

            // User resource
            ['/users/christer', 'user', 'christer'],
            ['/users/christer/', 'user', 'christer'],
            ['/users/christer.json', 'user', 'christer', null, 'json'],
            ['/users/user_name', 'user', 'user_name'],
            ['/users/user-name', 'user', 'user-name'],

            // Images resource
            ['/users/christer/images', 'images', 'christer'],
            ['/users/christer/images/', 'images', 'christer'],
            ['/users/christer/images.json', 'images', 'christer', null, 'json'],
            ['/users/user_name/images', 'images', 'user_name'],
            ['/users/user-name/images', 'images', 'user-name'],

            // Image resource
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c', 'image', 'christer', 'a9b80ed42957fd508c617549cad07d6c'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c.png', 'image', 'christer', 'a9b80ed42957fd508c617549cad07d6c', 'png'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c.jpg', 'image', 'christer', 'a9b80ed42957fd508c617549cad07d6c', 'jpg'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c.gif', 'image', 'christer', 'a9b80ed42957fd508c617549cad07d6c', 'gif'],
            ['/users/user_name/images/a9b80ed42957fd508c617549cad07d6c', 'image', 'user_name', 'a9b80ed42957fd508c617549cad07d6c'],
            ['/users/user-name/images/a9b80ed42957fd508c617549cad07d6c', 'image', 'user-name', 'a9b80ed42957fd508c617549cad07d6c'],

            // Metadata resource
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/meta', 'metadata', 'christer', 'a9b80ed42957fd508c617549cad07d6c'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/meta/', 'metadata', 'christer', 'a9b80ed42957fd508c617549cad07d6c'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/meta.json', 'metadata', 'christer', 'a9b80ed42957fd508c617549cad07d6c', 'json'],
            ['/users/user_name/images/a9b80ed42957fd508c617549cad07d6c/meta', 'metadata', 'user_name', 'a9b80ed42957fd508c617549cad07d6c'],
            ['/users/user-name/images/a9b80ed42957fd508c617549cad07d6c/meta', 'metadata', 'user-name', 'a9b80ed42957fd508c617549cad07d6c'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/metadata', 'metadata', 'christer', 'a9b80ed42957fd508c617549cad07d6c'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/metadata/', 'metadata', 'christer', 'a9b80ed42957fd508c617549cad07d6c'],
            ['/users/christer/images/a9b80ed42957fd508c617549cad07d6c/metadata.json', 'metadata', 'christer', 'a9b80ed42957fd508c617549cad07d6c', 'json'],
            ['/users/user_name/images/a9b80ed42957fd508c617549cad07d6c/metadata', 'metadata', 'user_name', 'a9b80ed42957fd508c617549cad07d6c'],
            ['/users/user-name/images/a9b80ed42957fd508c617549cad07d6c/metadata', 'metadata', 'user-name', 'a9b80ed42957fd508c617549cad07d6c'],
        ];
    }

    /**
     * @dataProvider getValidRoutes
     * @covers Imbo\Router::route
     */
    public function testCanMatchValidRoutes($route, $resource, $user = null, $imageIdentifier = null, $extension = null) {
        $this->request->expects($this->once())->method('getPathInfo')->will($this->returnValue($route));
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));

        $this->router->route($this->request);

        $route = $this->request->getRoute();

        $this->assertSame($user, $route->get('user'));
        $this->assertSame($imageIdentifier, $route->get('imageIdentifier'));
        $this->assertSame($extension, $route->get('extension'));
        $this->assertSame($resource, (string) $route);
    }
}
