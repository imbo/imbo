<?php
/**
 * Imbo
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
 * @package Imbo
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */

namespace Imbo\Resource;

use Imbo\Database\Exception as DatabaseException;

/**
 * @package Imbo
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */
class ImagesTest extends ResourceTests {
    protected function getNewResource() {
        return new Images();
    }

    public function testSetGetQuery() {
        $resource = $this->getNewResource();
        $query = $this->getMock('Imbo\Resource\Images\QueryInterface');
        $this->assertSame($resource, $resource->setQuery($query));
        $this->assertSame($query, $resource->getQuery());
    }

    /**
     * @expectedException Imbo\Resource\Exception
     * @expectedExceptionMessage Database error: message
     * @expectedExceptionCode 500
     */
    public function testGetWhenDatabaseThrowsAnException() {
        $resource = $this->getNewResource();
        $parameterContainer = $this->getMock('Imbo\Http\ParameterContainerInterface');

        $this->database->expects($this->once())->method('getImages')->will($this->throwException(new DatabaseException('message', 500)));
        $this->request->expects($this->once())->method('getQuery')->will($this->returnValue($parameterContainer));

        $resource->get($this->request, $this->response, $this->database, $this->storage);
    }

    public function testSuccessfulGetWithNoQueryParams() {
        $publicKey = md5(microtime());
        $images = array();

        $parameterContainer = $this->getMock('Imbo\Http\ParameterContainerInterface');
        $query = $this->getMock('Imbo\Resource\Images\QueryInterface');
        $writer = $this->getMock('Imbo\Http\Response\ResponseWriterInterface');
        $writer->expects($this->once())->method('write')->with($images, $this->request, $this->response);

        $resource = $this->getNewResource();
        $resource->setQuery($query);
        $resource->setResponseWriter($writer);

        $this->request->expects($this->once())->method('getQuery')->will($this->returnValue($parameterContainer));
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($publicKey));
        $this->database->expects($this->once())->method('getImages')->with($publicKey, $query)->will($this->returnValue($images));

        $resource->get($this->request, $this->response, $this->database, $this->storage);
    }

    public function testSuccessfulGetWithAllQueryParams() {
        $publicKey = md5(microtime());
        $images = array();

        $params = $this->getMock('Imbo\Http\ParameterContainerInterface');
        $query = $this->getMock('Imbo\Resource\Images\QueryInterface');

        $params->expects($this->any())->method('has')->will($this->returnValue(true));
        $params->expects($this->any())->method('get')->will($this->returnCallback(function($param) {
            switch ($param) {
                case 'page': return 1;
                case 'num': return 2;
                case 'metadata': return '1';
                case 'from': return 3;
                case 'to': return 4;
                case 'query': return json_encode(array('foo', 'bar'));
            }
        }));

        $query->expects($this->once())->method('page')->with(1);
        $query->expects($this->once())->method('num')->with(2);
        $query->expects($this->once())->method('returnMetadata')->with('1');
        $query->expects($this->once())->method('from')->with(3);
        $query->expects($this->once())->method('to')->with(4);
        $query->expects($this->once())->method('metadataQuery')->with(array('foo', 'bar'));

        $writer = $this->getMock('Imbo\Http\Response\ResponseWriterInterface');
        $writer->expects($this->once())->method('write')->with($images, $this->request, $this->response);

        $resource = $this->getNewResource();
        $resource->setQuery($query);
        $resource->setResponseWriter($writer);

        $this->request->expects($this->once())->method('getQuery')->will($this->returnValue($params));
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($publicKey));
        $this->database->expects($this->once())->method('getImages')->with($publicKey, $query)->will($this->returnValue($images));

        $resource->get($this->request, $this->response, $this->database, $this->storage);
    }
}
