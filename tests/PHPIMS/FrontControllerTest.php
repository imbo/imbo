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

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class PHPIMS_FrontControllerTest extends PHPUnit_Framework_TestCase {
    /**
     * Front controller instance
     *
     * @var PHPIMS_FrontController
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
        'database' => array(
            'driver' => 'PHPIMS_Database_Driver_Test',
        ),
        'operation' => array(
            'factory' => __CLASS__,
        ),
    );

    /**
     * Factory used in this test as a stand-in for PHPIMS_Operation
     */
    static public function factory($operation, $hash = null) {
        return self::$mocks[$operation];
    }

    /**
     * Set up method
     */
    public function setUp() {
        $this->controller = new PHPIMS_FrontController($this->controllerConfig);
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
     * Get a PHPIMS_Operation_Abstract mock object
     *
     * @param string $operationClass The class name of the operation to mock
     */
    protected function getOperationMock($operationClass) {
        $response = $this->getMock('PHPIMS_Server_Response');

        $operation = $this->getMock($operationClass);
        $operation->expects($this->once())->method('init')->with($this->controllerConfig)->will($this->returnValue($operation));
        $operation->expects($this->once())->method('preExec')->will($this->returnValue($operation));
        $operation->expects($this->once())->method('exec')->will($this->returnValue($operation));
        $operation->expects($this->once())->method('postExec')->will($this->returnValue($operation));

        return $operation;
    }

    public function testIsValidMethodWithSupportedMethods() {
        $this->assertTrue(PHPIMS_FrontController::isValidMethod('POST'));
        $this->assertTrue(PHPIMS_FrontController::isValidMethod('GET'));
        $this->assertTrue(PHPIMS_FrontController::isValidMethod('HEAD'));
        $this->assertTrue(PHPIMS_FrontController::isValidMethod('DELETE'));
    }

    public function testIsValidMethodWithInvalidMethod() {
        $this->assertFalse(PHPIMS_FrontController::isValidMethod('foobar'));
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
     * @expectedException PHPIMS_Exception
     */
    public function testHandleInvalidMethod() {
        $this->controller->handle('foobar', '/some/path');
    }

    /**
     * @expectedException PHPIMS_Exception
     * @expectedExceptionMessage Invalid hash: invalidhash
     */
    public function testHandleWithInvalidImageHash() {
        PHPIMS_Database_Driver_Test::$nextValidHashResult = false;
        $this->controller->handle('GET', '/invalidhash/extra');
    }

    public function testHandleAddImage() {
        $operation = $this->getOperationMock('PHPIMS_Operation_AddImage');
        self::$mocks['PHPIMS_Operation_AddImage'] = $operation;

        $this->controller->handle('POST', '');
    }

    public function testHandleEditImage() {
        $operation = $this->getOperationMock('PHPIMS_Operation_EditImage');
        self::$mocks['PHPIMS_Operation_EditImage'] = $operation;
        PHPIMS_Database_Driver_Test::$nextValidHashResult = true;

        $this->controller->handle('POST', 'some hash value');
    }

    public function testHandleGetImage() {
        $operation = $this->getOperationMock('PHPIMS_Operation_GetImage');
        self::$mocks['PHPIMS_Operation_GetImage'] = $operation;
        PHPIMS_Database_Driver_Test::$nextValidHashResult = true;

        $this->controller->handle('GET', 'some hash value');
    }

    public function testHandleGetMetadata() {
        $operation = $this->getOperationMock('PHPIMS_Operation_GetMetadata');
        self::$mocks['PHPIMS_Operation_GetMetadata'] = $operation;
        PHPIMS_Database_Driver_Test::$nextValidHashResult = true;

        $this->controller->handle('GET', 'some hash value/meta');
    }

    public function testHandleDeleteImage() {
        $operation = $this->getOperationMock('PHPIMS_Operation_DeleteImage');
        self::$mocks['PHPIMS_Operation_DeleteImage'] = $operation;
        PHPIMS_Database_Driver_Test::$nextValidHashResult = true;

        $this->controller->handle('DELETE', 'some hash value');
    }

    /**
     * @expectedException PHPIMS_Exception
     * @expectedExceptionMessage Unsupported operation
     */
    public function testHandleUnsupportedOperation() {
        $this->controller->handle('GET', 'some hash value/metadata');
    }
}