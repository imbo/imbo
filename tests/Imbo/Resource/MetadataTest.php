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

/**
 * @package Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class MetadataTest extends ResourceTests {
    protected function getNewResource() {
        return new Metadata();
    }

    /**
     * @covers Imbo\Resource\Metadata::delete
     */
    public function testDelete() {
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->database->expects($this->once())->method('deleteMetadata')->with($this->publicKey, $this->imageIdentifier);

        $writer = $this->getMock('Imbo\Http\Response\ResponseWriterInterface');
        $writer->expects($this->once())->method('write')->with($this->isType('array'), $this->request, $this->response);

        $resource = $this->getNewResource();
        $resource->setResponseWriter($writer);

        $resource->delete($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @covers Imbo\Resource\Metadata::post
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage Missing JSON data
     * @expectedExceptionCode 400
     */
    public function testPostWithNoMetadata() {
        $paramContainer = $this->getMock('Imbo\Http\ParameterContainerInterface');
        $paramContainer->expects($this->once())->method('has')->with('metadata')->will($this->returnValue(false));

        $this->request->expects($this->any())->method('getRequest')->will($this->returnValue($paramContainer));
        $this->request->expects($this->any())->method('getRawData')->will($this->returnValue(null));

        $resource = $this->getNewResource();
        $resource->post($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @covers Imbo\Resource\Metadata::post
     * @expectedException Imbo\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid JSON data
     * @expectedExceptionCode 400
     */
    public function testPostWithInvalidMetadata() {
        $paramContainer = $this->getMock('Imbo\Http\ParameterContainerInterface');
        $paramContainer->expects($this->once())->method('has')->with('metadata')->will($this->returnValue(false));

        $this->request->expects($this->any())->method('getRequest')->will($this->returnValue($paramContainer));
        $this->request->expects($this->any())->method('getRawData')->will($this->returnValue('some string'));

        $resource = $this->getNewResource();
        $resource->post($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @covers Imbo\Resource\Metadata::post
     */
    public function testPostWithDataInPostParams() {
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));

        $paramContainer = $this->getMock('Imbo\Http\ParameterContainerInterface');
        $paramContainer->expects($this->once())->method('has')->with('metadata')->will($this->returnValue(true));
        $paramContainer->expects($this->once())->method('get')->with('metadata')->will($this->returnValue('{"foo":"bar"}'));

        $this->request->expects($this->any())->method('getRequest')->will($this->returnValue($paramContainer));
        $this->database->expects($this->once())->method('updateMetadata')->with($this->publicKey, $this->imageIdentifier, array('foo' => 'bar'));
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));

        $writer = $this->getMock('Imbo\Http\Response\ResponseWriterInterface');
        $writer->expects($this->once())->method('write')->with($this->isType('array'), $this->request, $this->response);

        $resource = $this->getNewResource();
        $resource->setResponseWriter($writer);


        $resource->post($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @covers Imbo\Resource\Metadata::post
     */
    public function testPostWithDataInRawBody() {
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));

        $paramContainer = $this->getMock('Imbo\Http\ParameterContainerInterface');
        $paramContainer->expects($this->once())->method('has')->with('metadata')->will($this->returnValue(false));

        $this->request->expects($this->once())->method('getRequest')->will($this->returnValue($paramContainer));
        $this->request->expects($this->once())->method('getRawData')->will($this->returnValue('{"some":"value"}'));

        $this->database->expects($this->once())->method('updateMetadata')->with($this->publicKey, $this->imageIdentifier, array('some' => 'value'));
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));

        $writer = $this->getMock('Imbo\Http\Response\ResponseWriterInterface');
        $writer->expects($this->once())->method('write')->with($this->isType('array'), $this->request, $this->response);

        $resource = $this->getNewResource();
        $resource->setResponseWriter($writer);

        $resource->post($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @covers Imbo\Resource\Metadata::get
     */
    public function testGetWhenResponseIsNotModified() {
        $lastModified = 'Mon, 10 Jan 2011 13:37:00 GMT';
        $etag = '"' . md5($this->publicKey . $this->imageIdentifier . $lastModified) . '"';

        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));

        $requestHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $requestHeaders->expects($this->any())->method('get')->will($this->returnCallback(function($param) use ($lastModified, $etag) {
            if ($param === 'if-modified-since') {
                return $lastModified;
            } else if ($param === 'if-none-match') {
                return $etag;
            }
        }));

        $this->request->expects($this->once())->method('getHeaders')->will($this->returnValue($requestHeaders));

        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->once())->method('set')->with('ETag', $etag);

        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));
        $this->database->expects($this->once())->method('getLastModified')->with($this->publicKey, $this->imageIdentifier)->will($this->returnValue($lastModified));

        $this->response->expects($this->once())->method('setNotModified');

        $resource = $this->getNewResource();
        $resource->get($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @covers Imbo\Resource\Metadata::get
     */
    public function testGetWhenResponseIsModified() {
        $lastModified = 'Mon, 10 Jan 2011 13:37:00 GMT';
        $etag = '"' . md5($this->publicKey . $this->imageIdentifier . $lastModified) . '"';
        $metadataInDatabase = array('foo' => 'bar');

        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->once())->method('getImageIdentifier')->will($this->returnValue($this->imageIdentifier));

        $requestHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $this->request->expects($this->once())->method('getHeaders')->will($this->returnValue($requestHeaders));

        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->at(0))->method('set')->with('ETag', $etag);
        $responseHeaders->expects($this->at(1))->method('set')->with('Last-Modified', $lastModified);
        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));

        $this->database->expects($this->once())->method('getLastModified')->with($this->publicKey, $this->imageIdentifier)->will($this->returnValue($lastModified));

        $this->database->expects($this->once())->method('getMetadata')->with($this->publicKey, $this->imageIdentifier)->will($this->returnValue($metadataInDatabase));

        $writer = $this->getMock('Imbo\Http\Response\ResponseWriterInterface');
        $writer->expects($this->once())->method('write')->with($metadataInDatabase, $this->request, $this->response);

        $resource = $this->getNewResource();
        $resource->setResponseWriter($writer);

        $resource->get($this->request, $this->response, $this->database, $this->storage);
    }
}
