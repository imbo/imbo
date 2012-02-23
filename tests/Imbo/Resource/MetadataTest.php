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
 * @link https://github.com/christeredvartsen/imbo
 */

namespace Imbo\Resource;

/**
 * @package Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */
class MetadataTest extends ResourceTests {
    protected function getNewResource() {
        return new Metadata();
    }

    public function testDelete() {
        $imageIdentifier = md5(microtime());
        $publicKey = md5(microtime());

        $writer = $this->getMock('Imbo\Http\Response\ResponseWriterInterface');
        $writer->expects($this->once())->method('write')->with($this->isType('array'), $this->request, $this->response);

        $resource = $this->getNewResource();
        $resource->setResponseWriter($writer);

        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($imageIdentifier));
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($publicKey));
        $this->database->expects($this->once())->method('deleteMetadata')->with($publicKey, $imageIdentifier);

        $resource->delete($this->request, $this->response, $this->database, $this->storage);
    }

    public function testPost() {
        $imageIdentifier = md5(microtime());
        $publicKey = md5(microtime());

        $rawMetadata = array('some' => 'data');
        $metadata = json_encode($rawMetadata);

        $writer = $this->getMock('Imbo\Http\Response\ResponseWriterInterface');
        $writer->expects($this->once())->method('write')->with($this->isType('array'), $this->request, $this->response);

        $resource = $this->getNewResource();
        $resource->setResponseWriter($writer);

        $paramContainer = $this->getMock('Imbo\Http\ParameterContainerInterface');
        $paramContainer->expects($this->once())->method('has')->with('metadata')->will($this->returnValue(true));
        $paramContainer->expects($this->once())->method('get')->with('metadata')->will($this->returnValue($metadata));

        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($imageIdentifier));
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($publicKey));
        $this->request->expects($this->any())->method('getRequest')->will($this->returnValue($paramContainer));

        $this->database->expects($this->once())->method('updateMetadata')->with($publicKey, $imageIdentifier, $rawMetadata);

        $resource->post($this->request, $this->response, $this->database, $this->storage);
    }

    public function testPostWithDataInRawBody() {
        $imageIdentifier = md5(microtime());
        $publicKey = md5(microtime());

        $rawMetadata = array('some' => 'data');
        $metadata = json_encode($rawMetadata);

        $writer = $this->getMock('Imbo\Http\Response\ResponseWriterInterface');
        $writer->expects($this->once())->method('write')->with($this->isType('array'), $this->request, $this->response);

        $resource = $this->getNewResource();
        $resource->setResponseWriter($writer);

        $paramContainer = $this->getMock('Imbo\Http\ParameterContainerInterface');
        $paramContainer->expects($this->once())->method('has')->with('metadata')->will($this->returnValue(false));

        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($imageIdentifier));
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($publicKey));
        $this->request->expects($this->once())->method('getRequest')->will($this->returnValue($paramContainer));
        $this->request->expects($this->once())->method('getRawData')->will($this->returnValue($metadata));

        $this->database->expects($this->once())->method('updateMetadata')->with($publicKey, $imageIdentifier, $rawMetadata);

        $resource->post($this->request, $this->response, $this->database, $this->storage);
    }

    public function testGetWhenResponseIsNotModified() {
        $publicKey = 'some key';
        $imageIdentifier = 'b8533858299b04af3afc9a3713e69358';
        $lastModified = 'Mon, 10 Jan 2011 13:37:00 GMT';
        $etag = '"b0ef1f0bef5ac57b8d1365a51f3fc1ca"';

        $requestHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $requestHeaders->expects($this->any())->method('get')->will($this->returnCallback(function($param) use ($lastModified, $etag) {
            if ($param === 'if-modified-since') {
                return $lastModified;
            } else if ($param === 'if-none-match') {
                return $etag;
            }
        }));

        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->once())->method('set')->with('ETag', $etag);

        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($publicKey));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($imageIdentifier));
        $this->request->expects($this->once())->method('getHeaders')->will($this->returnValue($requestHeaders));

        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));
        $this->response->expects($this->once())->method('setNotModified');

        $this->database->expects($this->once())->method('getLastModified')->with($publicKey, $imageIdentifier)->will($this->returnValue($lastModified));

        $resource = $this->getNewResource();
        $resource->get($this->request, $this->response, $this->database, $this->storage);
    }

    public function testGetWhenResponseIsModified() {
        $publicKey = 'some key';
        $imageIdentifier = 'b8533858299b04af3afc9a3713e69358';
        $lastModified = 'Mon, 10 Jan 2011 13:37:00 GMT';
        $etag = '"b0ef1f0bef5ac57b8d1365a51f3fc1ca"';
        $metadata = array('foo' => 'bar');

        $requestHeaders = $this->getMock('Imbo\Http\HeaderContainer');

        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->any())->method('set')->will($this->returnCallback(function ($key, $value) use ($lastModified, $etag) {
            if (
                ($key === 'Etag' && $value === $etag) ||
                ($key === 'Last-Modified' && $value === $lastModified)
            ) {
                return true;
            }

            return false;
        }));

        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($publicKey));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($imageIdentifier));
        $this->request->expects($this->once())->method('getHeaders')->will($this->returnValue($requestHeaders));

        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));

        $this->database->expects($this->once())->method('getLastModified')->with($publicKey, $imageIdentifier)->will($this->returnValue($lastModified));
        $this->database->expects($this->once())->method('getMetadata')->with($publicKey, $imageIdentifier)->will($this->returnValue($metadata));

        $writer = $this->getMock('Imbo\Http\Response\ResponseWriterInterface');
        $writer->expects($this->once())->method('write')->with($metadata, $this->request, $this->response);

        $resource = $this->getNewResource();
        $resource->setResponseWriter($writer);
        $resource->get($this->request, $this->response, $this->database, $this->storage);
    }
}
