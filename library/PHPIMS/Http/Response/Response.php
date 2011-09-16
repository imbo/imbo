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
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Http\Response;

use PHPIMS\Http\Response\Formatter\FormatterInterface;
use PHPIMS\Http\HeaderContainer;
use PHPIMS\Image\ImageInterface;
use PHPIMS\Exception;

/**
 * Response object from the server to the client
 *
 * @package PHPIMS
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class Response implements ResponseInterface {
    /**
     * Response writer used to format messages to the client (other than images)
     *
     * @var PHPIMS\Http\Response\ResponseWriterInterface
     */
    private $writer;

    /**
     * HTTP protocol version
     *
     * @var string
     */
    private $protocolVersion = '1.1';

    /**
     * Different status codes
     *
     * @var array
     */
    static public $statusCodes = array(
        // 1xx Informational
        100 => 'Continue',
        101 => 'Switching Protocols',

        // 2xx Success
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information', // 1.1
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',

        // 3xx Redirection
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other', // 1.1
        304 => 'Not Modified',
        305 => 'Use Proxy', // 1.1
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect', // 1.1

        // 4xx Client Error
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',

        // 5xx Server Error
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded',
    );

    /**
     * HTTP status code
     *
     * @var int
     */
    private $statusCode = 200;

    /**
     * Response headers
     *
     * @var PHPIMS\Http\HeaderContainer
     */
    private $headers;

    /**
     * The body of the response
     *
     * @var string
     */
    private $body;

    /**
     * Class constructor
     *
     * @param PHPIMS\Http\Response\ResponseWriterInterface $writer
     */
    public function __construct(ResponseWriterInterface $writer) {
        $this->writer = $writer;
        $this->headers = new HeaderContainer();
    }

    /**
     * @see PHPIMS\Http\Response\ResponseInterface::getStatusCode()
     */
    public function getStatusCode() {
        return $this->statusCode;
    }

    /**
     * @see PHPIMS\Http\Response\ResponseInterface::setStatusCode()
     */
    public function setStatusCode($code) {
        $this->statusCode = (int) $code;

        return $this;
    }

    /**
     * @see PHPIMS\Http\Response\ResponseInterface::getHeaders()
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * @see PHPIMS\Http\Response\ResponseInterface::getBody()
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * @see PHPIMS\Http\Response\ResponseInterface::setBody()
     */
    public function setBody($content) {
        if ($content instanceof ImageInterface) {
            $contentType = $content->getMimeType();
            $content = $content->getBlob();
        } else {
            $contentType = $this->writer->getContentType();
            $content = $this->writer->write($content);
        }

        // Store the content in the body
        $this->body = $content;

        // Set some content specific headers
        $this->headers->set('Content-Length', strlen($content));
        $this->headers->set('Content-Type', $contentType);

        return $this;
    }

    /**
     * @see PHPIMS\Http\Response\ResponseInterface::setError()
     */
    public function setError($code, $message) {
        $this->setStatusCode($code)
             ->setBody(array('error' => array(
                'code'      => $code,
                'message'   => $message,
                'timestamp' => gmdate('Y-m-d\TH:i\Z'))));

        return $this;
    }

    /**
     * @see PHPIMS\Http\Response\ResponseInterface::getProtocolVersion()
     */
    public function getProtocolVersion() {
        return $this->protocolVersion;
    }

    /**
     * @see PHPIMS\Http\Response\ResponseInterface::setProtocolVersion()
     */
    public function setProtocolVersion($version) {
        $this->protocolVersion = $version;

        return $this;
    }

    /**
     * @see PHPIMS\Http\Response\ResponseInterface::send()
     */
    public function send() {
        $this->sendHeaders();
        $this->sendContent();
    }

    /**
     * Send all headers to the client
     */
    private function sendHeaders() {
        if (headers_sent()) {
            return;
        }

        $statusCode = $this->getStatusCode();
        $statusLine = sprintf("HTTP/%s %d %s", $this->getProtocolVersion(), $statusCode, self::$statusCodes[$statusCode]);
        header($statusLine);

        // Fetch all headers
        $headers = $this->headers->getAll();

        // Closure that will translate the normalized header names to a prettier format (HTTP
        // header names are case insensitive anyways (RFC2616, section 4.2)
        $transform = function($name) {
            return preg_replace_callback('/^[a-z]|-[a-z]/', function($match) {
                return strtoupper($match[0]);
            }, $name);
        };

        // Send all headers to the client
        foreach ($headers as $name => $value) {
            header($transform($name) . ': ' . $value);
        }
    }

    /**
     * Send the content to the client
     */
    private function sendContent() {
        print($this->getBody());
    }
}
