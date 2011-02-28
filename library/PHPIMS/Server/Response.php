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

/**
 * Response object from the server to the client
 *
 * @package PHPIMS
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class PHPIMS_Server_Response {
    /**
     * Different status codes
     *
     * @var array
     */
    static public $codes = array(
        200 => 'OK',
        201 => 'Created',
    );

    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code = 200;

    /**
     * Response headers
     *
     * @var array
     */
    protected $headers = array();

    /**
     * The data to send
     *
     * The data to send back to the client. Will be sent as a json encoded array
     *
     * @var array
     */
    protected $data = array();

    /**
     * Class constructor
     *
     * @param int $code Optional HTTP status code
     * @param array $headers Optional headers
     * @param array $data Optional data
     */
    public function __construct($code = null, array $headers = null, array $data = null) {
        if ($code !== null) {
            $this->setCode($code);
        }

        if ($headers !== null) {
            $this->setHeaders($headers);
        }

        if ($data !== null) {
            $this->setData($data);
        }
    }

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
     * @return PHPIMS_Server_Response
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
     * @return PHPIMS_Server_Response
     */
    public function setHeaders(array $headers) {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Add a single header
     *
     * @param string $header A header string to add
     * @return PHPIMS_Server_Response
     */
    public function addHeader($header) {
        $this->headers[] = $header;

        return $this;
    }

    /**
     * Get the data
     *
     * @return array
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Set the data
     *
     * @param array $data The data to be encoded
     * @return PHPIMS_Server_Response
     */
    public function setData(array $data) {
        $this->data = $data;

        return $this;
    }

    /**
     * Magic to string method
     *
     * This magic method will encode the data array to a JSON string and return that
     *
     * @return string
     */
    public function __toString() {
        return json_encode($this->getData());
    }
}