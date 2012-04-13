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
 * @package Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo;

use Imbo\Http\Request\RequestInterface;

/**
 * @package Unittests
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
     * @var string
     */
    private $publicKey;

    /**
     * @var string
     */
    private $privateKey;

    /**
     * @var Imbo\Validate\ValidateInterface
     */
    private $timestampValidator;

    /**
     * @var Imbo\Validate\ValidateInterface
     */
    private $signatureValidator;

    /**
     * Set up method
     */
    public function setUp() {
        $this->publicKey = md5(microtime());
        $this->privateKey = md5(microtime());
        $this->timestampValidator = $this->getMock('Imbo\Validate\ValidateInterface');
        $this->signatureValidator = $this->getMock('Imbo\Validate\SignatureInterface');

        $container = new Container();
        $container->config = array(
            'auth' => array(
                $this->publicKey => $this->privateKey,
            ),
        );
        $container->database = $this->getMock('Imbo\Database\DatabaseInterface');
        $container->storage  = $this->getMock('Imbo\Storage\StorageInterface');
        $container->imageResource = $this->getMock('Imbo\Resource\Image');
        $container->imagesResource = $this->getMock('Imbo\Resource\Images');
        $container->metadataResource = $this->getMock('Imbo\Resource\Metadata');
        $container->eventManager = $this->getMock('Imbo\EventManager\EventManagerInterface');

        $this->controller = new FrontController($container, $this->timestampValidator, $this->signatureValidator);
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
        $request->expects($this->once())->method('setImageExtension')->with('jpg');

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
     * @covers Imbo\FrontController::auth
     * @expectedException Imbo\Exception
     * @expectedExceptionMessage Unknown public key
     * @expectedExceptionCode 404
     */
    public function testAuthWithUnknownPublicKey() {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('auth');
        $method->setAccessible(true);

        $request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $request->expects($this->once())->method('getPublicKey')->will($this->returnValue('some unknown key'));

        $response = $this->getMock('Imbo\Http\Response\ResponseInterface');

        $method->invoke($this->controller, $request, $response);
    }

    /**
     * @covers Imbo\FrontController::auth
     */
    public function testAuthWithSafeHttpMethod() {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('auth');
        $method->setAccessible(true);

        $request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $request->expects($this->once())->method('isUnsafe')->will($this->returnValue(false));

        $response = $this->getMock('Imbo\Http\Response\ResponseInterface');

        $this->assertNull($method->invoke($this->controller, $request, $response));
    }

    /**
     * @covers Imbo\FrontController::auth
     * @expectedException Imbo\Exception
     * @expectedExceptionMessage Missing required authentication parameter: signature
     * @expectedExceptionCode 400
     */
    public function testAuthWithMissingSignature() {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('auth');
        $method->setAccessible(true);

        $query = $this->getMock('Imbo\Http\ParameterContainerInterface');
        $query->expects($this->any())->method('has')->with('signature')->will($this->returnValue(false));

        $request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $request->expects($this->once())->method('getQuery')->will($this->returnValue($query));
        $request->expects($this->once())->method('isUnsafe')->will($this->returnValue(true));

        $response = $this->getMock('Imbo\Http\Response\ResponseInterface');

        $method->invoke($this->controller, $request, $response);
    }

    /**
     * @covers Imbo\FrontController::auth
     * @expectedException Imbo\Exception
     * @expectedExceptionMessage Missing required authentication parameter: timestamp
     * @expectedExceptionCode 400
     */
    public function testAuthWithMissingTimestamp() {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('auth');
        $method->setAccessible(true);

        $query = $this->getMock('Imbo\Http\ParameterContainerInterface');
        $query->expects($this->at(0))->method('has')->with('signature')->will($this->returnValue(true));
        $query->expects($this->at(1))->method('has')->with('timestamp')->will($this->returnValue(false));

        $request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $request->expects($this->once())->method('getQuery')->will($this->returnValue($query));
        $request->expects($this->once())->method('isUnsafe')->will($this->returnValue(true));

        $response = $this->getMock('Imbo\Http\Response\ResponseInterface');

        $method->invoke($this->controller, $request, $response);
    }

    /**
     * @covers Imbo\FrontController::auth
     * @expectedException Imbo\Exception
     * @expectedExceptionMessage Invalid timestamp:
     * @expectedExceptionCode 400
     */
    public function testAuthWithInvalidTimestamp() {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('auth');
        $method->setAccessible(true);

        $query = $this->getMock('Imbo\Http\ParameterContainerInterface');
        $query->expects($this->any())->method('has')->will($this->returnValue(true));
        $query->expects($this->once())->method('get')->with('timestamp')->will($this->returnValue('some string'));

        $request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $request->expects($this->once())->method('getQuery')->will($this->returnValue($query));
        $request->expects($this->once())->method('isUnsafe')->will($this->returnValue(true));

        $this->timestampValidator->expects($this->once())->method('isValid')->with('some string')->will($this->returnValue(false));

        $response = $this->getMock('Imbo\Http\Response\ResponseInterface');

        $method->invoke($this->controller, $request, $response);
    }

    /**
     * @covers Imbo\FrontController::auth
     * @expectedException Imbo\Exception
     * @expectedExceptionMessage Signature mismatch
     * @expectedExceptionCode 403
     */
    public function testAuthWithSignatureMismatch() {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('auth');
        $method->setAccessible(true);

        $query = $this->getMock('Imbo\Http\ParameterContainerInterface');
        $query->expects($this->any())->method('has')->will($this->returnValue(true));
        $query->expects($this->any())->method('get');
        $query->expects($this->any())->method('remove')->with($this->isType('string'))->will($this->returnSelf());

        $request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $request->expects($this->once())->method('getQuery')->will($this->returnValue($query));
        $request->expects($this->once())->method('isUnsafe')->will($this->returnValue(true));

        $this->timestampValidator->expects($this->once())->method('isValid')->will($this->returnValue(true));
        $this->signatureValidator->expects($this->once())->method('isValid')->will($this->returnValue(false));

        $this->signatureValidator->expects($this->once())->method('setHttpMethod')->will($this->returnSelf());
        $this->signatureValidator->expects($this->once())->method('setUrl')->will($this->returnSelf());
        $this->signatureValidator->expects($this->once())->method('setTimestamp')->will($this->returnSelf());
        $this->signatureValidator->expects($this->once())->method('setPublicKey')->will($this->returnSelf());
        $this->signatureValidator->expects($this->once())->method('setPrivateKey')->will($this->returnSelf());

        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');

        $response = $this->getMock('Imbo\Http\Response\ResponseInterface');
        $response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));

        $method->invoke($this->controller, $request, $response);
    }

    /**
     * @covers Imbo\FrontController::auth
     */
    public function testSuccessfulAuth() {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('auth');
        $method->setAccessible(true);

        $query = $this->getMock('Imbo\Http\ParameterContainerInterface');
        $query->expects($this->any())->method('has')->will($this->returnValue(true));
        $query->expects($this->any())->method('get');
        $query->expects($this->any())->method('remove')->with($this->isType('string'))->will($this->returnSelf());

        $request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $request->expects($this->once())->method('getQuery')->will($this->returnValue($query));
        $request->expects($this->once())->method('isUnsafe')->will($this->returnValue(true));

        $this->timestampValidator->expects($this->once())->method('isValid')->will($this->returnValue(true));
        $this->signatureValidator->expects($this->once())->method('isValid')->will($this->returnValue(true));

        $this->signatureValidator->expects($this->once())->method('setHttpMethod')->will($this->returnSelf());
        $this->signatureValidator->expects($this->once())->method('setUrl')->will($this->returnSelf());
        $this->signatureValidator->expects($this->once())->method('setTimestamp')->will($this->returnSelf());
        $this->signatureValidator->expects($this->once())->method('setPublicKey')->will($this->returnSelf());
        $this->signatureValidator->expects($this->once())->method('setPrivateKey')->will($this->returnSelf());

        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');

        $response = $this->getMock('Imbo\Http\Response\ResponseInterface');
        $response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));

        $this->assertTrue($method->invoke($this->controller, $request, $response));
    }

    /**
     * @covers Imbo\FrontController::handle
     * @expectedException Imbo\Exception
     * @expectedExceptionMessage I'm a teapot!
     * @expectedExceptionCode 418
     */
    public function testHandleBrew() {
        $request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $request->expects($this->once())->method('getMethod')->will($this->returnValue('BREW'));

        $response = $this->getMock('Imbo\Http\Response\ResponseInterface');

        $this->controller->handle($request, $response);
    }

    /**
     * @covers Imbo\FrontController::handle
     * @expectedException Imbo\Exception
     * @expectedExceptionMessage Unsupported HTTP method
     * @expectedExceptionCode 501
     */
    public function testHandleUnsupportedHttpMethod() {
        $request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $request->expects($this->once())->method('getMethod')->will($this->returnValue('TRACE'));

        $response = $this->getMock('Imbo\Http\Response\ResponseInterface');

        $this->controller->handle($request, $response);
    }
}
