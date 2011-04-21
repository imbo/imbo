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
    protected $controller = null;

    /**
     * Array of mock objects used by the stand-in operation factory
     *
     * This array should be filled with mock objects by the tests that needs them.
     *
     * @var array
     */
    static public $mocks = array();

    /**
     * Configuration for the controller
     *
     * @var array
     */
    protected $controllerConfig = array(
        'operation' => array(
            'factory' => __CLASS__,
        ),
    );

    /**
     * Factory used in this test as a stand-in for PHPIMS\Operation
     */
    static public function factory($operation, $imageIdentifier = null) {
        return self::$mocks[$operation];
    }

    /**
     * Set up method
     */
    public function setUp() {
        $this->controller = new FrontController($this->controllerConfig);
        self::$mocks = array();
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->controller = null;
        self::$mocks = array();
    }

    /**
     * Get a PHPIMS\Operation mock object
     *
     * @param string $operationClass The class name of the operation to mock
     */
    protected function getOperationMock($operationClass) {
        $response = $this->getMock('PHPIMS\\Server\\Response');

        $operation = $this->getMockBuilder($operationClass)->disableOriginalConstructor()->getMock();
        $operation->expects($this->once())->method('init')->with($this->controllerConfig)->will($this->returnValue($operation));
        $operation->expects($this->once())->method('preExec')->will($this->returnValue($operation));
        $operation->expects($this->once())->method('exec')->will($this->returnValue($operation));
        $operation->expects($this->once())->method('postExec')->will($this->returnValue($operation));

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

    public function testSetGetConfig() {
        $config = array(
            'foo' => 'bar',
            'bar' => 'foo',
        );
        $this->controller->setConfig($config);
        $this->assertSame($config, $this->controller->getConfig());
    }

    /**
     * @expectedException PHPIMS\Exception
     * @expectedExceptionCode 501
     */
    public function testHandleInvalidMethod() {
        $this->controller->handle('foobar', '/some/path');
    }

    /**
     * @expectedException PHPIMS\Exception
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Invalid request: /
     */
    public function testHandleInvalidRequest() {
        $this->controller->handle('GET', '/');
    }

    public function testHandleAddImage() {
        $operation = $this->getOperationMock('PHPIMS\\Operation\\AddImage');
        self::$mocks['PHPIMS\\Operation\\AddImage'] = $operation;

        $this->controller->handle('POST', md5(microtime()) . '.png');
    }

    public function testHandleEditImage() {
        $operation = $this->getOperationMock('PHPIMS\\Operation\\EditMetadata');
        self::$mocks['PHPIMS\\Operation\\EditMetadata'] = $operation;

        $this->controller->handle('POST', md5(microtime()) . '.png/meta');
    }

    public function testHandleGetImage() {
        $operation = $this->getOperationMock('PHPIMS\\Operation\\GetImage');
        self::$mocks['PHPIMS\\Operation\\GetImage'] = $operation;

        $this->controller->handle('GET', md5(microtime()) . '.png');
    }

    public function testHandleGetMetadata() {
        $operation = $this->getOperationMock('PHPIMS\\Operation\\GetMetadata');
        self::$mocks['PHPIMS\\Operation\\GetMetadata'] = $operation;

        $this->controller->handle('GET', md5(microtime()) . '.png/meta');
    }

    public function testHandleDeleteImage() {
        $operation = $this->getOperationMock('PHPIMS\\Operation\\DeleteImage');
        self::$mocks['PHPIMS\\Operation\\DeleteImage'] = $operation;

        $this->controller->handle('DELETE', md5(microtime()) . '.png');
    }

    public function testHandleDeleteMetadata() {
        $operation = $this->getOperationMock('PHPIMS\\Operation\\DeleteMetadata');
        self::$mocks['PHPIMS\\Operation\\DeleteMetadata'] = $operation;

        $this->controller->handle('DELETE', md5(microtime()) . '.png/meta');
    }

    /**
     * @expectedException PHPIMS\Exception
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Unsupported operation
     */
    public function testHandleUnsupportedOperation() {
        $this->controller->handle('GET', md5(microtime()) . '.png/metadata');
    }

    /**
     * @expectedException PHPIMS\Exception
     * @expectedExceptionCode 418
     */
    public function testHandleBrew() {
        $this->controller->handle('BREW', md5(microtime()) . '.png');
    }
}