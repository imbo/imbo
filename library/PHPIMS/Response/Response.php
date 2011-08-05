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

namespace PHPIMS\Server;

use PHPIMS\Image;
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
class Response {
    /**
     * Different status codes
     *
     * @var array
     */
    static public $codes = array(
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
    private $code = 200;

    /**
     * Response headers
     *
     * @var array
     */
    private $headers = array();

    /**
     * Content-Type of the response
     *
     * @var string
     */
    private $contentType = 'application/json; charset=utf-8';

    /**
     * The body of the response
     *
     * The data to send back to the client. Will be sent as a json encoded array
     *
     * @var array
     */
    private $body = array();

    /**
     * Optional image attached to the response
     *
     * @var PHPIMS\Image
     */
    private $image = null;

    /**
     * Get the status code
     *
     * @return int
     */
    public function getCode() {
        return $this->code;
    }

    /**
     * Set the code
     *
     * @param int $code The HTTP status code to use in the response
     * @return PHPIMS\Server\Response
     */
    public function setCode($code) {
        $this->code = (int) $code;

        return $this;
    }

    /**
     * Get all headers
     *
     * @return array
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * Set all headers
     *
     * @param array $headers An array of headers to set
     * @return PHPIMS\Server\Response
     */
    public function setHeaders(array $headers) {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }

        return $this;
    }

    /**
     * Add a single header
     *
     * @param string $name The header name
     * @param mixed $value The header value
     * @return PHPIMS\Server\Response
     */
    public function setHeader($name, $value) {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * Set custom headers (prefixed with "X-PHPIMS-")
     *
     * @param array $headers Headers to set
     * @return \PHPIMS\Server\Response
     */
    public function setCustomHeaders(array $headers) {
        foreach ($headers as $name => $value) {
            $this->setCustomHeader($name, $value);
        }

        return $this;
    }

    /**
     * Set a custom header (prefixed with "X-PHPIMS-")
     *
     * @param string $name The header name
     * @param mixed $value The header value
     */
    public function setCustomHeader($name, $value) {
        return $this->setHeader('X-PHPIMS-' . $name, $value);
    }

    /**
     * Remove a single header element
     *
     * @param string $name The name of the header. For instance 'Location'
     * @return PHPIMS\Server\Response
     */
    public function removeHeader($name) {
        unset($this->headers[$name]);

        return $this;
    }

    /**
     * Get the Content-Type
     *
     * @return string
     */
    public function getContentType() {
        return $this->contentType;
    }

    /**
     * Set the Content-Type
     *
     * @param string $type The type to set. For instance "application/json" or "image/png"
     * @return PHPIMS\Server\Response
     */
    public function setContentType($type) {
        $this->contentType = $type;

        return $this;
    }

    /**
     * Get the body
     *
     * @return array
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * Set the body
     *
     * @param array $body The body content
     * @return PHPIMS\Server\Response
     */
    public function setBody(array $body) {
        $this->body = $body;

        return $this;
    }

    /**
     * Get the image
     *
     * @return PHPIMS\Image
     */
    public function getImage() {
        return $this->image;
    }

    /**
     * Set the image
     *
     * @param PHPIMS\Image $image The image object
     * @return PHPIMS\Server\Response
     */
    public function setImage(Image $image) {
        $this->image = $image;

        return $this;
    }

    /**
     * See if the response has an image attached to it
     *
     * @return boolean
     */
    public function hasImage() {
        return !($this->image === null);
    }

    /**
     * Create a response based on an exception object
     *
     * @param PHPIMS\Exception $e
     * @return PHPIMS\Server\Response
     */
    static public function fromException(Exception $e) {
        $response = new static();
        $response->setCode($e->getCode())
                 ->setBody(array('error' => array('code'      => $e->getCode(),
                                                  'message'   => $e->getMessage(),
                                                  'timestamp' => gmdate('Y-m-d\TH:i\Z')),
        ));

        return $response;
    }
}
