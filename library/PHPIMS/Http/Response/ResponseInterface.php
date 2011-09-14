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
 * @subpackage Interfaces
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Http\Response;

use PHPIMS\Exception;

/**
 * Response interface
 *
 * @package PHPIMS
 * @subpackage Interfaces
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
interface ResponseInterface {
    /**
     * Get the status code
     *
     * @return int
     */
    function getStatusCode();

    /**
     * Set the status code
     *
     * @param int $code The HTTP status code to use in the response
     * @return PHPIMS\Http\Response\ResponseInterface
     */
    function setStatusCode($code);

    /**
     * Get all headers as an associative array
     *
     * @return array
     */
    function getHeaders();

    /**
     * Set all headers
     *
     * @param array $headers An array of headers to set
     * @return PHPIMS\Http\Response\ResponseInterface
     */
    function setHeaders(array $headers);

    /**
     * Set a single header
     *
     * @param string $name The header name
     * @param mixed $value The header value
     * @return PHPIMS\Http\Response\ResponseInterface
     */
    function setHeader($name, $value);

    /**
     * Remove a single header element
     *
     * @param string $name The name of the header. For instance 'Location'
     * @return PHPIMS\Http\Response\ResponseInterface
     */
    function removeHeader($name);

    /**
     * Get the body
     *
     * @return string
     */
    function getBody();

    /**
     * Set the body
     *
     * @param string $body The body content
     * @return PHPIMS\Http\Response\ResponseInterface
     */
    function setBody($body);

    /**
     * Set an error message
     *
     * This method should update the status code and store the error message in the body of the
     * response.
     *
     * @param int $code The HTTP status code
     * @param string $message Error message that will be sent to the client
     * @return PHPIMS\Http\Response\ResponseInterface
     */
    function setError($code, $message);

    /**
     * Get the HTTP protocol version
     *
     * @return string
     */
    function getProtocolVersion();

    /**
     * Set the protocol version header
     *
     * @param string $version The version to set
     * @return PHPIMS\Http\Response\ResponseInterface
     */
    function setProtocolVersion($version);

    /**
     * Send all headers to the client (including the status line)
     */
    function sendHeaders();

    /**
     * Send the content to the client
     */
    function sendContent();

    /**
     * Send the headers and the content to the client
     */
    function send();
}
