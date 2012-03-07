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
 * @package Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\Resource;

use DateTime;

/**
 * @package Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
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
        $lastModified = 'Thu, 12 Jan 2012 16:13:35 GMT';
        $etag = '"' . md5($lastModified) . '"';

        $requestHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $requestHeaders->expects($this->any())->method('get')->will($this->returnCallback(function ($key) use ($etag, $lastModified) {
            if ($key === 'if-modified-since') {
                return $lastModified;
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
            $lastModified = 'Thu, 12 Jan 2012 16:13:35 GMT';

            if ($key === 'if-modified-since') {
                return $lastModified;
            } else if ($key === 'if-none-match') {
                return '"' . md5($lastModified) . '"';
            }
        }));

        $numImages = 42;
        $date = new DateTime('@' . time());
        $lastModified = $date->format('D, d M Y H:i:s') . ' GMT';
        $etag = '"' . md5($lastModified) . '"';

        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->at(0))->method('set')->with('ETag', $etag);
        $responseHeaders->expects($this->at(1))->method('set')->with('Last-Modified', $lastModified);

        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->once())->method('getHeaders')->will($this->returnValue($requestHeaders));

        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));

        $this->database->expects($this->once())->method('getNumImages')->with($this->publicKey)->will($this->returnValue($numImages));
        $this->database->expects($this->once())->method('getLastModified')->with($this->publicKey)->will($this->returnValue($lastModified));

        $writer = $this->getMock('Imbo\Http\Response\ResponseWriter');
        $writer->expects($this->once())->method('write')->with($this->isType('array'), $this->request, $this->response);

        $resource = $this->getNewResource();
        $resource->setResponseWriter($writer);

        $resource->get($this->request, $this->response, $this->database, $this->storage);
    }
}
