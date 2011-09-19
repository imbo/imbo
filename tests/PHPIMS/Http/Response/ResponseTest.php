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

namespace PHPIMS\Http\Response;

use PHPIMS\Exception;

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class ResponseTest extends \PHPUnit_Framework_TestCase {
    /**
     * Response instance
     *
     * @var PHPIMS\Http\Response\Response
     */
    private $response;

    /**
     * Writer instance
     *
     * @var PHPIMS\Http\Response\ResponseWriterInterface
     */
    private $writer;

    /**
     * Set up method
     */
    public function setUp() {
        $this->writer = $this->getMock('PHPIMS\Http\Response\ResponseWriterInterface');
        $this->response = new Response($this->writer);
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->writer = null;
        $this->response = null;
    }

    public function testSetGetStatusCode() {
        $code = 404;
        $this->response->setStatusCode($code);
        $this->assertSame($code, $this->response->getStatusCode());
    }

    public function testSetGetProtocolVersion() {
        // Assert default version
        $this->assertSame('1.1', $this->response->getProtocolVersion());
        $this->response->setProtocolVersion('1.0');
        $this->assertSame('1.0', $this->response->getProtocolVersion());
    }

    public function testSetGetHeaders() {
        $headers = $this->getMock('PHPIMS\Http\HeaderContainer');
        $this->response->setHeaders($headers);
        $this->assertSame($headers, $this->response->getHeaders());
    }

    public function testSetBodyWithArray() {
        $body = array('some' => 'data');
        $this->writer->expects($this->once())->method('write')->with($body)->will($this->returnValue('formatted data'));
        $this->writer->expects($this->once())->method('getContentType')->will($this->returnValue('text/plain'));
        $this->response->setBody($body);
        $this->assertSame('formatted data', $this->response->getBody());
        $this->assertSame(strlen('formatted data'), $this->response->getHeaders()->get('content-length'));
        $this->assertSame('text/plain', $this->response->getHeaders()->get('content-type'));
    }

    public function testSetBodyWithImageInstance() {
        $image = $this->getMock('PHPIMS\Image\ImageInterface');
        $image->expects($this->once())->method('getBlob')->will($this->returnValue('some binary data'));
        $image->expects($this->once())->method('getMimeType')->will($this->returnValue('image/png'));

        $this->response->setBody($image);

        $this->assertSame('some binary data', $this->response->getBody());
        $this->assertSame(strlen('some binary data'), $this->response->getHeaders()->get('content-length'));
        $this->assertSame('image/png', $this->response->getHeaders()->get('content-type'));
    }

    public function testSetError() {
        $code = 404;
        $message  = 'Image not found';
        $this->writer->expects($this->once())->method('write')->will($this->returnValue('Encoded error message'));

        $this->response->setError($code, $message);

        $this->assertSame(404, $this->response->getStatusCode());

        $this->assertSame('Encoded error message', $this->response->getBody());
    }

    public function testSendContent() {
        $content = 'some content';

        $this->writer->expects($this->once())->method('write')->will($this->returnValue('some content'));
        $this->writer->expects($this->once())->method('getContentType');

        $this->response->setBody(array('some data'));

        ob_start();
        $this->response->send();
        $output = ob_get_clean();

        $this->assertSame($output, $content);
    }
}
