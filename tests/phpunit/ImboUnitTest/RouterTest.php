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

/**
 * @covers Imbo\Router
 * @group unit
 * @group router
 */
class RouterTest extends \PHPUnit_Framework_TestCase {
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
                              ->setMethods(array('getPathInfo', 'getMethod'))
                              ->getMock();
    }

    /**
     * Tear down the router instance
     */
    public function tearDown() {
        $this->router = null;
        $this->request = null;
    }

    /**
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage I'm a teapot
     * @expectedExceptionCode 418
     * @covers Imbo\Router::route
     */
    public function testCanBeATeaPot() {
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('BREW'));
        $this->router->route($this->request);
    }

    /**
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage Unsupported HTTP method
     * @expectedExceptionCode 501
     * @covers Imbo\Router::route
     */
    public function testThrowsExceptionOnUnsupportedHttpMethod() {
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('TRACE'));
        $this->router->route($this->request);
    }

    /**
     * Return invalid routes for the resolve method
     *
     * @return array[]
     */
    public function getInvalidRoutes() {
        return array(
            array('/foobar'),
            array('/status.json/'),
            array('/users/Christer'),
            array('/users/christer.json/'),
            array('/users/Christer.json/'),
            array('/users/christer/images.json/'),
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c/'),
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c.gif/'),
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c/meta.json/'),
            array('/s/asdfghjk'),
            array('/s/asdfghj.jpg'),
        );
    }

    /**
     * @dataProvider getInvalidRoutes
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage Not Found
     * @expectedExceptionCode 404
     * @covers Imbo\Router::route
     */
    public function testThrowsExceptionWhenNoRouteMatches($route) {
        $this->request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $this->request->expects($this->once())->method('getPathInfo')->will($this->returnValue($route));
        $this->router->route($this->request);
    }

    /**
     * Returns valid routes for the router
     *
     * @return array[]
     */
    public function getValidRoutes() {
        return array(
            // Global short URL resource
            array('/s/asdfghj', 'globalshorturl'),
            array('/s/1234567', 'globalshorturl'),
            array('/s/1234asd', 'globalshorturl'),
            array('/s/aAbB012', 'globalshorturl'),

            // Short URLs
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c/shorturls', 'shorturls', 'christer', 'a9b80ed42957fd508c617549cad07d6c'),
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c/shorturls/', 'shorturls', 'christer', 'a9b80ed42957fd508c617549cad07d6c'),
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c/shorturls.json', 'shorturls', 'christer', 'a9b80ed42957fd508c617549cad07d6c', 'json'),
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c/shorturls.xml', 'shorturls', 'christer', 'a9b80ed42957fd508c617549cad07d6c', 'xml'),

            // Short URL
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c/shorturls/asdfghj', 'shorturl', 'christer', 'a9b80ed42957fd508c617549cad07d6c'),
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c/shorturls/1234567', 'shorturl', 'christer', 'a9b80ed42957fd508c617549cad07d6c'),
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c/shorturls/1234asd', 'shorturl', 'christer', 'a9b80ed42957fd508c617549cad07d6c'),
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c/shorturls/aAbB012', 'shorturl', 'christer', 'a9b80ed42957fd508c617549cad07d6c'),

            // Status resource
            array('/status', 'status'),
            array('/status/', 'status'),
            array('/status.json', 'status', null, null, 'json'),
            array('/status.xml', 'status', null, null, 'xml'),

            // User resource
            array('/users/christer', 'user', 'christer'),
            array('/users/christer/', 'user', 'christer'),
            array('/users/christer.json', 'user', 'christer', null, 'json'),
            array('/users/christer.xml', 'user', 'christer', null, 'xml'),
            array('/users/user_name', 'user', 'user_name'),
            array('/users/user-name', 'user', 'user-name'),

            // Images resource
            array('/users/christer/images', 'images', 'christer'),
            array('/users/christer/images/', 'images', 'christer'),
            array('/users/christer/images.json', 'images', 'christer', null, 'json'),
            array('/users/christer/images.xml', 'images', 'christer', null, 'xml'),
            array('/users/user_name/images', 'images', 'user_name'),
            array('/users/user-name/images', 'images', 'user-name'),

            // Image resource
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c', 'image', 'christer', 'a9b80ed42957fd508c617549cad07d6c'),
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c.png', 'image', 'christer', 'a9b80ed42957fd508c617549cad07d6c', 'png'),
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c.jpg', 'image', 'christer', 'a9b80ed42957fd508c617549cad07d6c', 'jpg'),
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c.gif', 'image', 'christer', 'a9b80ed42957fd508c617549cad07d6c', 'gif'),
            array('/users/user_name/images/a9b80ed42957fd508c617549cad07d6c', 'image', 'user_name', 'a9b80ed42957fd508c617549cad07d6c'),
            array('/users/user-name/images/a9b80ed42957fd508c617549cad07d6c', 'image', 'user-name', 'a9b80ed42957fd508c617549cad07d6c'),

            // Metadata resource
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c/meta', 'metadata', 'christer', 'a9b80ed42957fd508c617549cad07d6c'),
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c/meta/', 'metadata', 'christer', 'a9b80ed42957fd508c617549cad07d6c'),
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c/meta.json', 'metadata', 'christer', 'a9b80ed42957fd508c617549cad07d6c', 'json'),
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c/meta.xml', 'metadata', 'christer', 'a9b80ed42957fd508c617549cad07d6c', 'xml'),
            array('/users/user_name/images/a9b80ed42957fd508c617549cad07d6c/meta', 'metadata', 'user_name', 'a9b80ed42957fd508c617549cad07d6c'),
            array('/users/user-name/images/a9b80ed42957fd508c617549cad07d6c/meta', 'metadata', 'user-name', 'a9b80ed42957fd508c617549cad07d6c'),
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c/metadata', 'metadata', 'christer', 'a9b80ed42957fd508c617549cad07d6c'),
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c/metadata/', 'metadata', 'christer', 'a9b80ed42957fd508c617549cad07d6c'),
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c/metadata.json', 'metadata', 'christer', 'a9b80ed42957fd508c617549cad07d6c', 'json'),
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c/metadata.xml', 'metadata', 'christer', 'a9b80ed42957fd508c617549cad07d6c', 'xml'),
            array('/users/user_name/images/a9b80ed42957fd508c617549cad07d6c/metadata', 'metadata', 'user_name', 'a9b80ed42957fd508c617549cad07d6c'),
            array('/users/user-name/images/a9b80ed42957fd508c617549cad07d6c/metadata', 'metadata', 'user-name', 'a9b80ed42957fd508c617549cad07d6c'),
        );
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
