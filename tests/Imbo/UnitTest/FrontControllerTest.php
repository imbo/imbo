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

use Imbo\FrontController,
    Imbo\Container,
    Imbo\Http\Request\RequestInterface;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\FrontController
 */
class FrontControllerTest extends \PHPUnit_Framework_TestCase {
    /**
     * Front controller instance
     *
     * @var Imbo\FrontController
     */
    private $controller;

    /**
     * @var Imbo\Container
     */
    private $container;

    /**
     * @var string
     */
    private $publicKey;

    /**
     * @var string
     */
    private $privateKey;

    /**
     * Set up method
     */
    public function setUp() {
        $this->publicKey = md5(microtime());
        $this->privateKey = md5(microtime());

        $this->container = new Container();
        $this->container->config = array(
            'auth' => array(
                $this->publicKey => $this->privateKey,
            ),
        );
        $this->container->request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $this->container->response = $this->getMock('Imbo\Http\Response\ResponseInterface');
        $this->container->database = $this->getMock('Imbo\Database\DatabaseInterface');
        $this->container->storage = $this->getMock('Imbo\Storage\StorageInterface');

        $this->container->imageResource = $this->getMock('Imbo\Resource\Image');
        $this->container->imagesResource = $this->getMock('Imbo\Resource\Images');
        $this->container->metadataResource = $this->getMock('Imbo\Resource\Metadata');
        $this->container->eventManager = $this->getMock('Imbo\EventManager\EventManagerInterface');

        $this->controller = new FrontController($this->container);
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->controller = null;
    }

    public function getResolveData() {
        $imageIdentifier = md5(microtime());
        $publicKey = md5(microtime());

        return array(
            array('/users/' . $publicKey . '/images/' . $imageIdentifier . '/meta', 'Imbo\Resource\Metadata'),
            array('/users/' . $publicKey . '/images/' . $imageIdentifier, 'Imbo\Resource\Image'),
            array('/users/' . $publicKey . '/images/' . $imageIdentifier . '.jpg', 'Imbo\Resource\Image'),
            array('/users/' . $publicKey . '/images', 'Imbo\Resource\Images'),
            array('/users/' . $publicKey, 'Imbo\Resource\User'), // Not located in the DIC, should work nonetheless
        );
    }

    /**
     * @covers Imbo\FrontController::resolveResource
     * @dataProvider getResolveData()
     */
    public function testResolveResource($path, $resourceClass) {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('resolveResource');
        $method->setAccessible(true);

        $request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $request->expects($this->once())->method('getPath')->will($this->returnValue($path));
        $request->expects($this->once())->method('setPublicKey');
        $this->assertInstanceOf($resourceClass, $method->invoke($this->controller, $request));
    }

    /**
     * @covers Imbo\FrontController::resolveResource
     */
    public function testReolveResourceWhenGivenImageUrlWithExtension() {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('resolveResource');
        $method->setAccessible(true);

        $imageIdentifier = md5(microtime());
        $publicKey = 'key';
        $path = '/users/' . $publicKey . '/images/' . $imageIdentifier . '.jpg';

        $request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $request->expects($this->once())->method('getPath')->will($this->returnValue($path));
        $request->expects($this->once())->method('setPublicKey')->with($publicKey);
        $request->expects($this->once())->method('setImageIdentifier')->with($imageIdentifier);
        $request->expects($this->once())->method('setExtension')->with('jpg');

        $this->assertInstanceOf('Imbo\Resource\Image', $method->invoke($this->controller, $request));
    }

    /**
     * @covers Imbo\FrontController::resolveResource
     * @expectedException Imbo\Exception
     * @expectedExceptionCode 404
     */
    public function testResolveResourceWithInvalidRequest() {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('resolveResource');
        $method->setAccessible(true);
        $request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $request->expects($this->once())->method('getPath')->will($this->returnValue('foobar'));
        $method->invoke($this->controller, $request);
    }

    /**
     * @covers Imbo\FrontController::run
     * @expectedException Imbo\Exception
     * @expectedExceptionMessage I'm a teapot!
     * @expectedExceptionCode 418
     */
    public function testRespondWith418WhenHttpMethodIsBrew() {
        $this->container->request->expects($this->once())->method('getMethod')->will($this->returnValue('BREW'));
        $this->controller->run();
    }

    /**
     * @covers Imbo\FrontController::run
     * @expectedException Imbo\Exception
     * @expectedExceptionMessage Unsupported HTTP method
     * @expectedExceptionCode 501
     */
    public function testRespondWith501WhenHttpMethodIsNotSupported() {
        $this->container->request->expects($this->once())->method('getMethod')->will($this->returnValue('TRACE'));
        $this->controller->run();
    }
}
