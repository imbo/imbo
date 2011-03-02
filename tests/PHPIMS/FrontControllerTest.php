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
     * Configuration for the controller
     *
     * @var array
     */
    protected $controllerConfig = array(
        'database' => array(
            'driver' => 'PHPIMS_Database_Driver_Test',
        ),
    );

    /**
     * Set up method
     */
    public function setUp() {
        $this->controller = new PHPIMS_FrontController($this->controllerConfig);
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->controller = null;
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
}