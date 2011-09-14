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
    private $controller;

    /**
     * Set up method
     */
    public function setUp() {
        $config = array(
            'database' => $this->getMock('PHPIMS\Database\DatabaseInterface'),
            'storage' => $this->getMock('PHPIMS\Storage\StorageInterface'),
        );
        $this->controller = new FrontController($config);
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->controller = null;
    }

    public function testResolveResourceWithImageRequest() {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('resolveResource');
        $method->setAccessible(true);
        $request = $this->getMock('PHPIMS\Http\Request\RequestInterface');
        $request->expects($this->once())->method('isImageRequest')->will($this->returnValue(true));
        $this->assertInstanceOf('PHPIMS\Resource\Image', $method->invoke($this->controller, $request));
    }

    public function testResolveResourceWithImagesRequest() {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('resolveResource');
        $method->setAccessible(true);
        $request = $this->getMock('PHPIMS\Http\Request\RequestInterface');
        $request->expects($this->once())->method('isImageRequest')->will($this->returnValue(false));
        $request->expects($this->once())->method('isImagesRequest')->will($this->returnValue(true));
        $this->assertInstanceOf('PHPIMS\Resource\Images', $method->invoke($this->controller, $request));
    }

    public function testResolveResourceWithMetadataRequest() {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('resolveResource');
        $method->setAccessible(true);
        $request = $this->getMock('PHPIMS\Http\Request\RequestInterface');
        $request->expects($this->once())->method('isImageRequest')->will($this->returnValue(false));
        $request->expects($this->once())->method('isImagesRequest')->will($this->returnValue(false));
        $request->expects($this->once())->method('isMetadataRequest')->will($this->returnValue(true));
        $this->assertInstanceOf('PHPIMS\Resource\Metadata', $method->invoke($this->controller, $request));
    }

    /**
     * @expectedException PHPIMS\Exception
     * @expectedExceptionMessage Invalid request
     * @expectedExceptionCode 400
     */
    public function testResolveResourceWithInvalidRequest() {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('resolveResource');
        $method->setAccessible(true);
        $request = $this->getMock('PHPIMS\Http\Request\RequestInterface');
        $request->expects($this->once())->method('isImageRequest')->will($this->returnValue(false));
        $request->expects($this->once())->method('isImagesRequest')->will($this->returnValue(false));
        $request->expects($this->once())->method('isMetadataRequest')->will($this->returnValue(false));
        $method->invoke($this->controller, $request);
    }
}
