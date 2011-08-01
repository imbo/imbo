<?php
/**
 * PHPIMS
 *
 * Copyright (c) 2011 Christer Edvartsen <cogo@starzinger.net>
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
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS;

use \Mockery as m;

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class FrontControllerTest extends \PHPUnit_Framework_TestCase {
    /**
     * Front controller instance
     *
     * @var PHPIMS\FrontController
     */
    private $controller;

    /**
     * Public key
     *
     * @var string
     */
    private $publicKey;

    /**
     * Private key
     *
     * @var string
     */
    private $privateKey;

    /**
     * Set up method
     */
    public function setUp() {
        $this->publicKey = md5(microtime());
        $this->privateKey = md5(microtime());
        $config = array(
            'database' => m::mock('PHPIMS\\Database\\DriverInterface'),
            'storage' => m::mock('PHPIMS\\Storage\\DriverInterface'),
            'auth' => array(
                $this->publicKey => $this->privateKey,
            ),
        );
        $this->controller = new FrontController($config);
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->controller = null;
    }

    /**
     * Get a PHPIMS\Operation mock object
     *
     * @param string $operationClass The class name of the operation to mock
     */
    private function getOperationMock($operationClass) {
        $operation = m::mock($operationClass);
        $operation->shouldReceive('init')->once()->andReturn($operation);
        $operation->shouldReceive('preExec')->once()->andReturn($operation);
        $operation->shouldReceive('exec')->once()->andReturn($operation);
        $operation->shouldReceive('postExec')->once()->andReturn($operation);

        return $operation;
    }

    public function testIsValidMethodWithSupportedMethods() {
        $this->assertTrue(FrontController::isValidMethod('POST'));
        $this->assertTrue(FrontController::isValidMethod('GET'));
        $this->assertTrue(FrontController::isValidMethod('HEAD'));
        $this->assertTrue(FrontController::isValidMethod('DELETE'));
    }

    public function testIsValidMethodWithInvalidMethod() {
        $this->assertFalse(FrontController::isValidMethod('foobar'));
    }

    /**
     * @expectedException PHPIMS\Exception
     * @expectedExceptionCode 501
     */
    public function testHandleInvalidMethod() {
        $this->controller->handle('/some/path', 'foobar');
    }

    /**
     * @expectedException PHPIMS\Exception
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Unknown resource
     */
    public function testHandleInvalidRequest() {
        $this->controller->handle('/foobar', 'GET');
    }

    /**
     * @expectedException PHPIMS\Exception
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Unsupported operation
     */
    public function testHandleUnsupportedOperation() {
        $this->controller->handle($this->publicKey . '/images', 'DELETE');
    }

    /**
     * @expectedException PHPIMS\Exception
     * @expectedExceptionCode 418
     */
    public function testHandleBrew() {
        $this->controller->handle($this->publicKey . '/' . md5(microtime()) . '.png', 'BREW');
    }

    public function testResolveOperation() {
        $imageIdentifier = md5(microtime()) . '.png';
        $resource = $imageIdentifier;

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('resolveOperation');
        $method->setAccessible(true);

        $database = m::mock('PHPIMS\\Database\\DriverInterface');
        $storage = m::mock('PHPIMS\\Storage\\DriverInterface');

        $this->assertInstanceOf('PHPIMS\\Operation\\GetImages', $method->invokeArgs($this->controller, array('images', 'GET')));

        $this->assertInstanceOf('PHPIMS\\Operation\\GetImage', $method->invokeArgs($this->controller, array($resource, 'GET', $imageIdentifier)));
        $this->assertInstanceOf('PHPIMS\\Operation\\HeadImage', $method->invokeArgs($this->controller, array($resource, 'HEAD', $imageIdentifier)));
        $this->assertInstanceOf('PHPIMS\\Operation\\AddImage', $method->invokeArgs($this->controller, array($resource, 'PUT', $imageIdentifier)));
        $this->assertInstanceOf('PHPIMS\\Operation\\DeleteImage', $method->invokeArgs($this->controller, array($resource, 'DELETE', $imageIdentifier)));

        $extra = 'meta';
        $resource .= '/meta';

        $this->assertInstanceOf('PHPIMS\\Operation\\GetImageMetadata', $method->invokeArgs($this->controller, array($resource, 'GET', $imageIdentifier, $extra)));
        $this->assertInstanceOf('PHPIMS\\Operation\\EditImageMetadata', $method->invokeArgs($this->controller, array($resource, 'POST', $imageIdentifier, $extra)));
        $this->assertInstanceOf('PHPIMS\\Operation\\DeleteImageMetadata', $method->invokeArgs($this->controller, array($resource, 'DELETE', $imageIdentifier, $extra)));
    }

    /**
     * @expectedException PHPIMS\Exception
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Unknown public key
     */
    public function testHandleValidOperationWithValidButUnknownPublicKey() {
        $resource = md5(microtime()) . '/' . md5(microtime()) . '.png';
        $method = 'GET';

        $this->controller->handle($resource, $method);
    }
}
