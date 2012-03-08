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
 * @package Interfaces
 * @subpackage Http
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\Http\Response;

use Imbo\Http\HeaderContainer;

/**
 * Response interface
 *
 * @package Interfaces
 * @subpackage Http
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
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
     * @return Imbo\Http\Response\ResponseInterface
     */
    function setStatusCode($code);

    /**
     * Get the header container
     *
     * @return Imbo\Http\HeaderContainer
     */
    function getHeaders();

    /**
     * Set the header container
     *
     * @param Imbo\Http\HeaderContainer
     * @return Imbo\Http\Response\ResponesInterface
     */
    function setHeaders(HeaderContainer $headers);

    /**
     * Get the body
     *
     * @return string
     */
    function getBody();

    /**
     * Set the body
     *
     * @param Imbo\Image\ImageInterface|array $content Either an image instance, or an array
     * @return Imbo\Http\Response\ResponseInterface
     */
    function setBody($content);

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
     * @return Imbo\Http\Response\ResponseInterface
     */
    function setProtocolVersion($version);

    /**
     * Send the response to the client (headers and content)
     */
    function send();

    /**
     * Prepare the response to send 304 Not Modified to the client
     *
     * @return Imbo\Http\Response\ResponseInterface
     */
    function setNotModified();
}
