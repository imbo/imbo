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

use Imbo\Resource\Resource,
    Imbo\Container;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\Resource\Resource
 */
class ResourceTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Imbo\Container
     */
    private $container;

    /**
     * @var Imbo\Resource\Resource
     */
    private $resource;

    public function setUp() {
        $this->container = $this->getMock('Imbo\Container');
        $this->resource = new ResourceImplementation();
    }

    public function tearDown() {
        $this->container = null;
        $this->resource = null;
    }

    /**
     * @covers Imbo\Resource\Resource::get
     * @expectedException Imbo\Exception
     * @expectedExceptionMessage Method not allowed
     * @expectedExceptionCode 405
     */
    public function testRespondWith405WhenGetIsNotAllowedAndHttpMethodIsGet() {
        $this->resource->get($this->container);
    }

    /**
     * @covers Imbo\Resource\Resource::put
     * @expectedException Imbo\Exception
     * @expectedExceptionMessage Method not allowed
     * @expectedExceptionCode 405
     */
    public function testRespondWith405WhenPutIsNotAllowedAndHttpMethodIsPut() {
        $this->resource->put($this->container);
    }

    /**
     * @covers Imbo\Resource\Resource::post
     * @expectedException Imbo\Exception
     * @expectedExceptionMessage Method not allowed
     * @expectedExceptionCode 405
     */
    public function testRespondWith405WhenPostIsNotAllowedAndHttpMethodIsPost() {
        $this->resource->post($this->container);
    }

    /**
     * @covers Imbo\Resource\Resource::delete
     * @expectedException Imbo\Exception
     * @expectedExceptionMessage Method not allowed
     * @expectedExceptionCode 405
     */
    public function testRespondWith405WhenDeleteIsNotAllowedAndHttpMethodIsDelete() {
        $this->resource->delete($this->container);
    }

    /**
     * @covers Imbo\Resource\Resource::head
     * @expectedException Imbo\Exception
     * @expectedExceptionMessage Method not allowed
     * @expectedExceptionCode 405
     */
    public function testRespondWith405WhenHeadIsNotAllowedAndHttpMethodIsHead() {
        $this->resource->head($this->container);
    }
}

class ResourceImplementation extends Resource {
    public function getAllowedMethods() {}
}
