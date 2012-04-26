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

use Imbo\Resource\User,
    DateTime;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\Resource\User
 */
class UserTest extends ResourceTests {
    protected function getNewResource() {
        return new User();
    }

    /**
     * @covers Imbo\Resource\User::get
     */
    public function testGetWhenDataIsNotModified() {
        $numImages = 42;
        $formattedDate = 'Thu, 12 Jan 2012 16:13:35 GMT';
        $lastModified = $this->getMock('DateTime');
        $lastModified->expects($this->once())->method('format')->will($this->returnValue(substr($formattedDate, 0, -4)));

        $etag = '"' . md5($formattedDate) . '"';

        $requestHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $requestHeaders->expects($this->any())->method('get')->will($this->returnCallback(function ($key) use ($etag, $formattedDate) {
            if ($key === 'if-modified-since') {
                return $formattedDate;
            } else if ($key === 'if-none-match') {
                return $etag;
            }
        }));

        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->once())->method('set')->with('ETag', $etag);

        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->once())->method('getHeaders')->will($this->returnValue($requestHeaders));

        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));
        $this->response->expects($this->once())->method('setNotModified');

        $this->database->expects($this->once())->method('getNumImages')->with($this->publicKey)->will($this->returnValue($numImages));
        $this->database->expects($this->once())->method('getLastModified')->with($this->publicKey)->will($this->returnValue($lastModified));

        $this->getNewResource()->get($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @covers Imbo\Resource\User::get
     */
    public function testGetWhenDataIsModified() {
        $requestHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $requestHeaders->expects($this->any())->method('get')->will($this->returnCallback(function ($key) {
            $lastModified = 'Thu, 13 Jan 2012 16:13:35 GMT';

            if ($key === 'if-modified-since') {
                return $lastModified;
            } else if ($key === 'if-none-match') {
                return '"' . md5($lastModified) . '"';
            }
        }));

        $numImages = 42;
        $formattedDate = 'Thu, 12 Jan 2012 16:13:35 GMT';
        $lastModified = $this->getMock('DateTime');
        $lastModified->expects($this->once())->method('format')->will($this->returnValue(substr($formattedDate, 0, -4)));

        $etag = '"' . md5($formattedDate) . '"';

        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->at(0))->method('set')->with('ETag', $etag);
        $responseHeaders->expects($this->at(1))->method('set')->with('Last-Modified', $formattedDate);

        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->once())->method('getHeaders')->will($this->returnValue($requestHeaders));

        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));

        $this->database->expects($this->once())->method('getNumImages')->with($this->publicKey)->will($this->returnValue($numImages));
        $this->database->expects($this->once())->method('getLastModified')->with($this->publicKey)->will($this->returnValue($lastModified));

        $this->response->expects($this->once())->method('setBody')->with($this->isType('array'));

        $this->getNewResource()->get($this->request, $this->response, $this->database, $this->storage);
    }
}
