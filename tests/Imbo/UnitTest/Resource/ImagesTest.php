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

use Imbo\Resource\Images,
    Imbo\Exception\DatabaseException;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\Resource\Images
 */
class ImagesTest extends ResourceTests {
    protected function getNewResource() {
        return new Images();
    }

    /**
     * @covers Imbo\Resource\Images::getQuery
     * @covers Imbo\Resource\Images::setQuery
     */
    public function testSetGetQuery() {
        $resource = $this->getNewResource();
        $query = $this->getMock('Imbo\Resource\Images\QueryInterface');
        $this->assertSame($resource, $resource->setQuery($query));
        $this->assertSame($query, $resource->getQuery());
    }

    /**
     * @covers Imbo\Resource\Images::get
     */
    public function testGetWhenResourceIsNotModified() {
        $formattedDate = 'Mon, 10 Jan 2011 13:37:00 GMT';
        $lastModified = $this->getMock('DateTime');
        $lastModified->expects($this->once())->method('format')->will($this->returnValue(substr($formattedDate, 0, -4)));

        $etag = '"' . md5($formattedDate) . '"';

        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));

        $requestHeaders = $this->getMock('Imbo\Http\ParameterContainerInterface');
        $requestHeaders->expects($this->any())->method('get')->will($this->returnCallback(function($key, $value) use ($formattedDate, $etag) {
            if ($key === 'if-modified-since') {
                return $formattedDate;
            } else if ($key === 'if-none-match') {
                return $etag;
            }

            return null;
        }));
        $this->request->expects($this->once())->method('getHeaders')->will($this->returnValue($requestHeaders));

        $responseHeaders = $this->getMock('Imbo\Http\ParameterContainerInterface');
        $responseHeaders->expects($this->once())->method('set')->with('ETag', $etag);
        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));

        $this->database->expects($this->once())->method('getLastModified')->will($this->returnValue($lastModified));

        $this->response->expects($this->once())->method('setNotModified');

        $this->getNewResource()->get($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @covers Imbo\Resource\Images::get
     */
    public function testSuccessfulGetWithNoQueryParams() {
        $images = array();

        $parameterContainer = $this->getMock('Imbo\Http\ParameterContainerInterface');
        $query = $this->getMock('Imbo\Resource\Images\QueryInterface');

        $resource = $this->getNewResource();
        $resource->setQuery($query);

        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->any())->method('set');

        $requestHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $requestHeaders->expects($this->any())->method('get');

        $this->request->expects($this->once())->method('getQuery')->will($this->returnValue($parameterContainer));
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->once())->method('getHeaders')->will($this->returnValue($requestHeaders));
        $this->database->expects($this->once())->method('getImages')->with($this->publicKey, $query)->will($this->returnValue($images));
        $this->database->expects($this->once())->method('getLastModified')->will($this->returnValue($this->getMock('DateTime')));
        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));
        $this->response->expects($this->once())->method('setBody')->with($images);

        $resource->get($this->request, $this->response, $this->database, $this->storage);
    }

    /**
     * @covers Imbo\Resource\Images::get
     */
    public function testSuccessfulGetWithAllQueryParams() {
        $images = array();

        $params = $this->getMock('Imbo\Http\ParameterContainerInterface');
        $query = $this->getMock('Imbo\Resource\Images\QueryInterface');

        $params->expects($this->any())->method('has')->will($this->returnValue(true));
        $params->expects($this->any())->method('get')->will($this->returnCallback(function($param) {
            switch ($param) {
                case 'page': return 1;
                case 'limit': return 2;
                case 'metadata': return '1';
                case 'from': return 3;
                case 'to': return 4;
                case 'query': return json_encode(array('foo', 'bar'));
            }
        }));

        $query->expects($this->once())->method('page')->with(1);
        $query->expects($this->once())->method('limit')->with(2);
        $query->expects($this->once())->method('returnMetadata')->with('1');
        $query->expects($this->once())->method('from')->with(3);
        $query->expects($this->once())->method('to')->with(4);
        $query->expects($this->once())->method('metadataQuery')->with(array('foo', 'bar'));

        $resource = $this->getNewResource();
        $resource->setQuery($query);

        $responseHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $responseHeaders->expects($this->any())->method('set');

        $requestHeaders = $this->getMock('Imbo\Http\HeaderContainer');
        $requestHeaders->expects($this->any())->method('get');

        $this->request->expects($this->once())->method('getQuery')->will($this->returnValue($params));
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($this->publicKey));
        $this->request->expects($this->once())->method('getHeaders')->will($this->returnValue($requestHeaders));
        $this->database->expects($this->once())->method('getImages')->with($this->publicKey, $query)->will($this->returnValue($images));
        $this->database->expects($this->once())->method('getLastModified')->will($this->returnValue($this->getMock('DateTime')));
        $this->response->expects($this->once())->method('getHeaders')->will($this->returnValue($responseHeaders));
        $this->response->expects($this->once())->method('setBody')->with($images);

        $resource->get($this->request, $this->response, $this->database, $this->storage);
    }
}
