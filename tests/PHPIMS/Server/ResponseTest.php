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

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class PHPIMS_Server_ResponseTest extends PHPUnit_Framework_TestCase {
    /**
     * Response instance
     *
     * @var PHPIMS_Server_Response
     */
    protected $response = null;

    /**
     * Set up method
     */
    public function setUp() {
        $this->response = new PHPIMS_Server_Response();
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->response = null;
    }

    public function testSetGetCode() {
        $code = 404;
        $this->response->setCode($code);
        $this->assertSame($code, $this->response->getCode());
    }

    public function testSetGetHeaders() {
        $headers = array(
            'Location: http://foo/bar',
            'x-Some: Value',
        );
        $this->response->setHeaders($headers);
        $this->assertSame($headers, $this->response->getHeaders());
    }

    public function testAddHeader() {
        $header1 = 'Location: http://foo/bar';
        $header2 = 'x-Some: Value';
        $headers = array($header1, $header2);
        $this->response->addHeader($header1);
        $this->response->addHeader($header2);
        $this->assertSame($headers, $this->response->getHeaders());
    }

    public function testSetGetData() {
        $data = array(
            'foo' => 'bar',
            'bar' => 'foo',
        );
        $this->response->setData($data);
        $this->assertSame($data, $this->response->getData());
    }

    public function testToStringMagicMethod() {
        $data = array('foo' => 'bar', 'bar' => 42);
        $this->response->setData($data);
        $this->assertSame(json_encode($data), (string) $this->response);
    }

    public function testUseFullConstructor() {
        $code = 404;
        $headers = array(
            'Location: http://foo/bar',
            'x-Some: Value',
        );
        $data = array(
            'foo' => 'bar',
            'bar' => 'foo',
        );
        $response = new PHPIMS_Server_Response($code, $headers, $data);
        $this->assertSame($code, $response->getCode());
        $this->assertSame($headers, $response->getHeaders());
        $this->assertSame($data, $response->getData());
    }
}