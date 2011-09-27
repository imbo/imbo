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

use PHPIMS\Database\Exception as DatabaseException;

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class ImagesTest extends ResourceTests {
    protected function getNewResource() {
        return new Images();
    }

    /**
     * @expectedException PHPIMS\Resource\Exception
     * @expectedExceptionMessage Database error: message
     * @expectedExceptionCode 500
     */
    public function testGetWhenDatabaseThrowsAnException() {
        $resource = $this->getNewResource();
        $parameterContainer = $this->getMock('PHPIMS\Http\ParameterContainerInterface');
        $parameterContainer->expects($this->any())->method('has')->will($this->returnValue(false));

        $this->database->expects($this->once())->method('getImages')->will($this->throwException(new DatabaseException('message', 500)));
        $this->request->expects($this->once())->method('getQuery')->will($this->returnValue($parameterContainer));

        $resource->get($this->request, $this->response, $this->database, $this->storage);
    }

    public function testSuccessfulGetWithNoQueryParams() {
        $publicKey = md5(microtime());
        $resource = $this->getNewResource();
        $images = array();
        $parameterContainer = $this->getMock('PHPIMS\Http\ParameterContainerInterface');
        $parameterContainer->expects($this->any())->method('has')->will($this->returnValue(false));

        $this->request->expects($this->once())->method('getQuery')->will($this->returnValue($parameterContainer));
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($publicKey));
        $this->database->expects($this->once())->method('getImages')->with($publicKey, $this->isInstanceOf('PHPIMS\Resource\Images\Query'))->will($this->returnValue($images));
        $this->response->expects($this->once())->method('setBody')->with($images);

        $resource->get($this->request, $this->response, $this->database, $this->storage);
    }

    public function ttestSuccessfulGetWithAllQueryParams() {
        $publicKey = md5(microtime());
        $resource = $this->getNewResource();
        $images = array();
        $parameterContainer = $this->getMock('PHPIMS\Http\ParameterContainerInterface');
        $parameterContainer->expects($this->any())->method('has')->will($this->returnValue(false));

        $this->request->expects($this->once())->method('getQuery')->will($this->returnValue($parameterContainer));
        $this->request->expects($this->once())->method('getPublicKey')->will($this->returnValue($publicKey));
        $this->database->expects($this->once())->method('getImages')->with($publicKey, $this->isInstanceOf('PHPIMS\Resource\Images\Query'))->will($this->returnValue($images));
        $this->response->expects($this->once())->method('setBody')->with($images);

        $resource->get($this->request, $this->response, $this->database, $this->storage);
    }
}
