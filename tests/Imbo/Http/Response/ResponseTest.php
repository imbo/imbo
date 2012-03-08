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

namespace Imbo\Http\Response;

use Imbo\Exception;

/**
 * @package Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\Http\Response\Response
 */
class ResponseTest extends \PHPUnit_Framework_TestCase {
    /**
     * Response instance
     *
     * @var Imbo\Http\Response\Response
     */
    private $response;

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
     * @covers Imbo\Http\Response\Response::setStatusCode
     * @covers Imbo\Http\Response\Response::getStatusCode
     */
    public function testSetGetStatusCode() {
        $code = 404;
        $this->assertSame($this->response, $this->response->setStatusCode($code));
        $this->assertSame($code, $this->response->getStatusCode());
    }

    /**
     * @covers Imbo\Http\Response\Response::getProtocolVersion
     * @covers Imbo\Http\Response\Response::setProtocolVersion
     */
    public function testSetGetProtocolVersion() {
        // Assert default version
        $this->assertSame('1.1', $this->response->getProtocolVersion());
        $this->assertSame($this->response, $this->response->setProtocolVersion('1.0'));
        $this->assertSame('1.0', $this->response->getProtocolVersion());
    }

    /**
     * @covers Imbo\Http\Response\Response::getHeaders
     * @covers Imbo\Http\Response\Response::setHeaders
     */
    public function testSetGetHeaders() {
        $headers = $this->getMock('Imbo\Http\HeaderContainer');
        $this->assertSame($this->response, $this->response->setHeaders($headers));
        $this->assertSame($headers, $this->response->getHeaders());
    }

    /**
     * @covers Imbo\Http\Response\Response::setBody
     * @covers Imbo\Http\Response\Response::getBody
     */
    public function testSetGetBody() {
        $body = 'some content';
        $this->assertSame($this->response, $this->response->setBody($body));
        $this->assertSame($body, $this->response->getBody());
    }

    /**
     * @covers Imbo\Http\Response\Response::setBody
     * @covers Imbo\Http\Response\Response::send
     */
    public function testSendContent() {
        $content = 'some content';
        $this->assertSame($this->response, $this->response->setBody($content));

        ob_start();
        $this->response->send();
        $output = ob_get_clean();

        $this->assertSame($output, $content);
    }

    /**
     * @covers Imbo\Http\Response\Response::setBody
     * @covers Imbo\Http\Response\Response::setStatusCode
     * @covers Imbo\Http\Response\Response::setNotModified
     * @covers Imbo\Http\Response\Response::getStatusCode
     * @covers Imbo\Http\Response\Response::getBody
     */
    public function testSetNotModified() {
        $this->assertSame($this->response, $this->response->setBody('some content'));
        $this->assertSame($this->response, $this->response->setStatusCode(200));

        $this->assertSame($this->response, $this->response->setNotModified());

        $this->assertSame(304, $this->response->getStatusCode());
        $this->assertEmpty($this->response->getBody());
    }
}
