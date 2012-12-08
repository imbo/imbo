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
    Imbo\Router;

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
     * @var FrontController
     */
    private $controller;

    /**
     * @var Container
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
     * @var Router
     */
    private $router;

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
        $this->container->eventManager = $this->getMock('Imbo\EventManager\EventManagerInterface');
        $this->container->router = $this->getMockBuilder('Imbo\Router')->disableOriginalConstructor()->getMock();

        $this->controller = new FrontController($this->container);
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->controller = null;
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

    /**
     * @covers Imbo\FrontController::run
     * @covers Imbo\FrontController::resolveResource
     */
    public function testCanHandleAValidRequest() {
        $this->container->request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $this->container->request->expects($this->once())->method('getPath')->will($this->returnValue('/status'));
        $this->container->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->container->request->expects($this->once())->method('setPrivateKey')->will($this->returnValue($this->privateKey));
        $this->container->request->expects($this->once())->method('getResource')->will($this->returnValue('status'));

        $resource = $this->getMock('Imbo\Resource\Status');
        $resource->expects($this->once())->method('get')->with($this->container);
        $resource->expects($this->once())->method('getAllowedMethods')->will($this->returnValue(array('HEAD', 'GET')));
        $this->container->router->expects($this->once())->method('resolve')->with('/status', $this->isType('array'))->will($this->returnValue($resource));

        $this->container->eventManager->expects($this->at(0))->method('trigger')->with('route.resolved', $this->isType('array'));
        $this->container->eventManager->expects($this->at(1))->method('trigger')->with('status.get.pre');
        $this->container->eventManager->expects($this->at(2))->method('trigger')->with('status.get.post');

        $responseHeaders = $this->getMock('Imbo\Http\ParameterContainerInterface');
        $responseHeaders->expects($this->at(0))->method('set')->with('Allow', 'HEAD, GET');
        $responseHeaders->expects($this->at(1))->method('set')->with('Vary', 'Accept');
        $this->container->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));

        $this->controller->run();
    }

    /**
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage Unknown Public Key
     * @expectedExceptionCode 404
     * @covers Imbo\FrontController::run
     */
    public function testThrowsExceptionWhenAnUnknownPublicKeyIsRequested() {
        $this->container->request->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
        $this->container->request->expects($this->once())->method('getPath')->will($this->returnValue('/status'));
        $this->container->request->expects($this->once())->method('getPublicKey')->will($this->returnValue('unknownPublicKey'));

        $resource = $this->getMock('Imbo\Resource\Status');
        $resource->expects($this->once())->method('getAllowedMethods')->will($this->returnValue(array('HEAD', 'GET')));
        $this->container->router->expects($this->once())->method('resolve')->with('/status', $this->isType('array'))->will($this->returnValue($resource));

        $this->container->eventManager->expects($this->at(0))->method('trigger')->with('route.resolved', $this->isType('array'));

        $responseHeaders = $this->getMock('Imbo\Http\ParameterContainerInterface');
        $responseHeaders->expects($this->at(0))->method('set')->with('Allow', $this->isType('string'));
        $this->container->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));

        $this->controller->run();
    }
}
