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

namespace PHPIMS\Response;

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
     * @var PHPIMS\Image\ImageInterface
     */
    private $image;

    /**
     * @see PHPIMS\Response\ResponseInterface::getCode()
     */
    public function getCode() {
        return $this->code;
    }

    /**
     * @see PHPIMS\Response\ResponseInterface::setCode()
     */
    public function setCode($code) {
        $this->code = (int) $code;

        return $this;
    }

    /**
     * @see PHPIMS\Response\ResponseInterface::getHeaders()
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * @see PHPIMS\Response\ResponseInterface::setHeaders()
     */
    public function setHeaders(array $headers) {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }

        return $this;
    }

    /**
     * @see PHPIMS\Response\ResponseInterface::setHeader()
     */
    public function setHeader($name, $value) {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * @see PHPIMS\Response\ResponseInterface::removeHeader()
     */
    public function removeHeader($name) {
        unset($this->headers[$name]);

        return $this;
    }

    /**
     * @see PHPIMS\Response\ResponseInterface::getContentType()
     */
    public function getContentType() {
        return $this->contentType;
    }

    /**
     * @see PHPIMS\Response\ResponseInterface::setContentType()
     */
    public function setContentType($type) {
        $this->contentType = $type;

        return $this;
    }

    /**
     * @see PHPIMS\Response\ResponseInterface::getBody()
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * @see PHPIMS\Response\ResponseInterface::setBody()
     */
    public function setBody(array $body) {
        $this->body = $body;

        return $this;
    }

    /**
     * @see PHPIMS\Response\ResponseInterface::getImage()
     */
    public function getImage() {
        return $this->image;
    }

    /**
     * @see PHPIMS\Response\ResponseInterface::setImage()
     */
    public function setImage(ImageInterface $image) {
        $this->image = $image;

        return $this;
    }

    /**
     * @see PHPIMS\Response\ResponseInterface::hasImage()
     */
    public function hasImage() {
        return !($this->image === null);
    }

    /**
     * Create a response based on an exception object
     *
     * @param PHPIMS\Exception $e
     * @return PHPIMS\Response\Response
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
