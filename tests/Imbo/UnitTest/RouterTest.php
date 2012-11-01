<?php
/**
 * Imbo
 *
 * Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * * The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\UnitTest;

use Imbo\Router,
    Imbo\Container,
    Imbo\Resource\Status as StatusResource,
    Imbo\Resource\User as UserResource,
    Imbo\Resource\Images as ImagesResource,
    Imbo\Resource\Image as ImageResource,
    Imbo\Resource\Metadata as MetadataResource;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\FrontController
 */
class RouterTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Router
     */
    private $router;

    /**
     * @var Container
     */
    private $container;

    /**
     * Set up the router instance
     *
     * @covers Imbo\Router::__construct
     */
    public function setUp() {
        $this->container = new Container();
        $this->router = new Router($this->container);
    }

    /**
     * Tear down the router instance
     */
    public function tearDown() {
        $this->container = null;
        $this->router = null;
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
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c.gif/'),
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c/meta.json/'),
        );
    }

    /**
     * @dataProvider getInvalidRoutes
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage Not Found
     * @expectedExceptionCode 404
     * @covers Imbo\Router::resolve
     */
    public function testThrowsExceptionWhenNoRouteMatches($route) {
        $matches = array();
        $this->router->resolve($route, $matches);
    }

    /**
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage Unknown Resource
     * @expectedExceptionCode 500
     * @covers Imbo\Router::resolve
     */
    public function testThrowsExceptionWhenRouteMatchesAndEntryDoesNotExistInContainer() {
        $matches = array();
        $this->router->resolve('/status', $matches);
    }

    /**
     * Returns valid routes for the router
     *
     * @return array[]
     */
    public function getValidRoutes() {
        return array(
            // Status resource
            array('/status', 'statusResource', new StatusResource()),
            array('/status/', 'statusResource', new StatusResource()),
            array('/status.json', 'statusResource', new StatusResource()),
            array('/status.xml', 'statusResource', new StatusResource()),
            array('/status.html', 'statusResource', new StatusResource()),

            // User resource
            array('/users/christer', 'userResource', new UserResource()),
            array('/users/christer/', 'userResource', new UserResource()),
            array('/users/christer.json', 'userResource', new UserResource()),
            array('/users/christer.xml', 'userResource', new UserResource()),
            array('/users/christer.html', 'userResource', new UserResource()),
            array('/users/user_name', 'userResource', new UserResource()),
            array('/users/user-name', 'userResource', new UserResource()),

            // Images resource
            array('/users/christer/images', 'imagesResource', new ImagesResource()),
            array('/users/christer/images/', 'imagesResource', new ImagesResource()),
            array('/users/christer/images.json', 'imagesResource', new ImagesResource()),
            array('/users/christer/images.xml', 'imagesResource', new ImagesResource()),
            array('/users/christer/images.html', 'imagesResource', new ImagesResource()),
            array('/users/user_name/images', 'imagesResource', new ImagesResource()),
            array('/users/user-name/images', 'imagesResource', new ImagesResource()),

            // Image resource
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c', 'imageResource', new ImageResource()),
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c/', 'imageResource', new ImageResource()),
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c.png', 'imageResource', new ImageResource()),
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c.jpg', 'imageResource', new ImageResource()),
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c.gif', 'imageResource', new ImageResource()),
            array('/users/user_name/images/a9b80ed42957fd508c617549cad07d6c', 'imageResource', new ImageResource()),
            array('/users/user-name/images/a9b80ed42957fd508c617549cad07d6c', 'imageResource', new ImageResource()),

            // Metadata resource
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c/meta', 'metadataResource', new MetadataResource()),
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c/meta/', 'metadataResource', new MetadataResource()),
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c/meta.json', 'metadataResource', new MetadataResource()),
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c/meta.xml', 'metadataResource', new MetadataResource()),
            array('/users/christer/images/a9b80ed42957fd508c617549cad07d6c/meta.html', 'metadataResource', new MetadataResource()),
            array('/users/user_name/images/a9b80ed42957fd508c617549cad07d6c/meta', 'metadataResource', new MetadataResource()),
            array('/users/user-name/images/a9b80ed42957fd508c617549cad07d6c/meta', 'metadataResource', new MetadataResource()),
        );
    }

    /**
     * @dataProvider getValidRoutes
     * @covers Imbo\Router::resolve
     */
    public function testCanMatchValidRoutes($route, $entry, $resource) {
        $this->container->$entry = $resource;
        $matches = array();
        $this->assertSame($resource, $this->router->resolve($route, $matches));
    }
}
