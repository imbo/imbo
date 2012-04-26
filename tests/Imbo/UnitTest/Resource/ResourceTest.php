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

namespace Imbo\UnitTest\Resource;

use Imbo\Resource\Resource;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\Resource\Resource
 */
class ResourceTest extends \PHPUnit_Framework_TestCase {
    public function setUp() {
        $this->resource = new ResourceImplementation();
        $this->request  = $this->getMock('Imbo\Http\Request\RequestInterface');
        $this->response = $this->getMock('Imbo\Http\Response\ResponseInterface');
        $this->database = $this->getMock('Imbo\Database\DatabaseInterface');
        $this->storage  = $this->getMock('Imbo\Storage\StorageInterface');
    }

    public function tearDown() {
        $this->resource = null;
        $this->request  = null;
        $this->response = null;
        $this->database = null;
        $this->storage  = null;
    }

    /**
     * @covers Imbo\Resource\Resource::get
     * @expectedException Imbo\Exception
     * @expectedExceptionMessage Method not allowed
     * @expectedExceptionCode 405
     */
    public function testGet() {
        $this->resource->get($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @covers Imbo\Resource\Resource::put
     * @expectedException Imbo\Exception
     * @expectedExceptionMessage Method not allowed
     * @expectedExceptionCode 405
     */
    public function testPut() {
        $this->resource->put($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @covers Imbo\Resource\Resource::post
     * @expectedException Imbo\Exception
     * @expectedExceptionMessage Method not allowed
     * @expectedExceptionCode 405
     */
    public function testPost() {
        $this->resource->post($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @covers Imbo\Resource\Resource::delete
     * @expectedException Imbo\Exception
     * @expectedExceptionMessage Method not allowed
     * @expectedExceptionCode 405
     */
    public function testDelete() {
        $this->resource->delete($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @covers Imbo\Resource\Resource::head
     * @expectedException Imbo\Exception
     * @expectedExceptionMessage Method not allowed
     * @expectedExceptionCode 405
     */
    public function testHead() {
        $this->resource->head($this->request, $this->response, $this->database, $this->storage);
    }
}

class ResourceImplementation extends Resource {
    public function getAllowedMethods() {}
}
