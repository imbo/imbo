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

use Imbo\Resource\Status;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\Resource\User
 */
class StatusTest extends ResourceTests {
    protected function getNewResource() {
        return new Status();
    }

    /**
     * @covers Imbo\Resource\Status::get
     */
    public function testStatusWhenDatabaseDriverFails() {
        $this->database->expects($this->once())->method('getStatus')->will($this->returnValue(false));
        $this->storage->expects($this->once())->method('getStatus')->will($this->returnValue(true));

        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->any())->method('set')->will($this->returnSelf());
        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));
        $this->response->expects($this->once())->method('setStatusCode')->with(500);

        $this->response->expects($this->once())->method('setBody')->with($this->isType('array'));

        $this->getNewResource()->get($this->container);
    }

    /**
     * @covers Imbo\Resource\Status::get
     */
    public function testStatusWhenStorageDriverFails() {
        $this->database->expects($this->once())->method('getStatus')->will($this->returnValue(true));
        $this->storage->expects($this->once())->method('getStatus')->will($this->returnValue(false));

        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->any())->method('set')->will($this->returnSelf());
        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));
        $this->response->expects($this->once())->method('setStatusCode')->with(500);

        $this->response->expects($this->once())->method('setBody')->with($this->isType('array'));

        $this->getNewResource()->get($this->container);
    }

    /**
     * @covers Imbo\Resource\Status::get
     */
    public function testStatusWhenBothDriversFails() {
        $this->database->expects($this->once())->method('getStatus')->will($this->returnValue(false));
        $this->storage->expects($this->once())->method('getStatus')->will($this->returnValue(false));

        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->any())->method('set')->will($this->returnSelf());
        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));
        $this->response->expects($this->once())->method('setStatusCode')->with(500);

        $this->response->expects($this->once())->method('setBody')->with($this->isType('array'));

        $this->getNewResource()->get($this->container);
    }

    /**
     * @covers Imbo\Resource\Status::get
     */
    public function testStatusWhenNoDriverFails() {
        $this->database->expects($this->once())->method('getStatus')->will($this->returnValue(true));
        $this->storage->expects($this->once())->method('getStatus')->will($this->returnValue(true));

        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->any())->method('set')->will($this->returnSelf());
        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));
        $this->response->expects($this->never())->method('setStatusCode');

        $this->response->expects($this->once())->method('setBody')->with($this->isType('array'));

        $this->getNewResource()->get($this->container);
    }
}
