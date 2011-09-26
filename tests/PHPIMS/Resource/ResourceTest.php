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

namespace PHPIMS\Resource;

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class ResourceTest extends \PHPUnit_Framework_TestCase {
    public function setUp() {
        $this->resource = new ResourceImplementation();
        $this->request  = $this->getMock('PHPIMS\Http\Request\RequestInterface');
        $this->response = $this->getMock('PHPIMS\Http\Response\ResponseInterface');
        $this->database = $this->getMock('PHPIMS\Database\DatabaseInterface');
        $this->storage  = $this->getMock('PHPIMS\Storage\StorageInterface');
    }

    public function tearDown() {
        $this->resource = null;
        $this->request  = null;
        $this->response = null;
        $this->database = null;
        $this->storage  = null;
    }

    /**
     * @expectedException PHPIMS\Exception
     * @expectedExceptionMessage Method not allowed
     * @expectedExceptionCode 405
     */
    public function testGet() {
        $this->resource->get($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @expectedException PHPIMS\Exception
     * @expectedExceptionMessage Method not allowed
     * @expectedExceptionCode 405
     */
    public function testPut() {
        $this->resource->put($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @expectedException PHPIMS\Exception
     * @expectedExceptionMessage Method not allowed
     * @expectedExceptionCode 405
     */
    public function testPost() {
        $this->resource->post($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @expectedException PHPIMS\Exception
     * @expectedExceptionMessage Method not allowed
     * @expectedExceptionCode 405
     */
    public function testDelete() {
        $this->resource->delete($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @expectedException PHPIMS\Exception
     * @expectedExceptionMessage Method not allowed
     * @expectedExceptionCode 405
     */
    public function testHead() {
        $this->resource->head($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @expectedException PHPIMS\Exception
     * @expectedExceptionMessage Method not allowed
     * @expectedExceptionCode 405
     */
    public function testOptions() {
        $this->resource->options($this->request, $this->response, $this->database, $this->storage);
    }
}

class ResourceImplementation extends Resource {}
