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

namespace Imbo\Client;

/**
 * @package Imbo
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */
class ResponseTest extends \PHPUnit_Framework_TestCase {
    /**
     * Response instance
     *
     * @var Imbo\Client\Response
     */
    protected $response = null;

    /**
     * Set up method
     */
    public function setUp() {
        $this->response = new Response();
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->response = null;
    }

    /**
     * Test the set and get methods for the headers attribute
     */
    public function testSetGetHeaders() {
        $headers = array(
            'Some header',
            'Another header',
        );

        $this->response->setHeaders($headers);
        $this->assertSame($headers, $this->response->getHeaders());
    }

    /**
     * Test the set and get methods for the body attribute
     */
    public function testSetGetBody() {
        $body = 'Content';

        $this->response->setBody($body);
        $this->assertSame($body, $this->response->getBody());
    }

    /**
     * Test the set and get methods for the statusCode attribute
     */
    public function testSetGetStatusCode() {
        $code = 404;
        $this->response->setStatusCode($code);
        $this->assertSame($code, $this->response->getStatusCode());
    }

    /**
     * Test the isSuccess method
     */
    public function testIsOk() {
        $this->response->setStatusCode(200);
        $this->assertTrue($this->response->isSuccess());
        $this->response->setStatusCode(404);
        $this->assertFalse($this->response->isSuccess());
    }

    /**
     * Test the magic __toString method
     */
    public function testMagicToStringMethod() {
        $body = 'Body content';
        $this->response->setBody($body);
        $this->assertSame($body, (string) $this->response);
    }

    public function testFactory() {
        $content = 'HTTP/1.1 200 OK' . PHP_EOL .
                   'Date: Wed, 02 Feb 2011 15:29:12 GMT' . PHP_EOL .
                   'Server: Apache/2.2.14 (Ubuntu)' . PHP_EOL .
                   'X-Powered-By: PHP/5.3.2-1ubuntu4.7' . PHP_EOL .
                   'Vary: Accept-Encoding' . PHP_EOL .
                   'Content-Length: 12' . PHP_EOL .
                   'Content-Type: text/html; charset=UTF-8' . PHP_EOL . PHP_EOL .
                   'Some content';

        $response = Response::factory($content);

        $this->assertInstanceOf('Imbo\Client\Response', $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Some content', $response->getBody());
        $this->assertSame(6, count($response->getHeaders()));
    }

    public function testAsArray() {
        $this->response->setBody(json_encode(array('foo' => 'bar')));
        $this->assertInternalType('array', $this->response->asArray());
    }

    public function testAsObject() {
        $this->response->setBody(json_encode(array('foo' => 'bar')));
        $this->assertInstanceOf('stdClass', $this->response->asObject());
    }
}