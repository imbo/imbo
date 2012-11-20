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

use Imbo\Http\HeaderContainer,
    Imbo\EventManager\EventInterface,
    Imbo\EventListener\ListenerInterface,
    Imbo\Exception,
    Imbo\Http\Request\RequestInterface;

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
interface ResponseInterface extends ListenerInterface {
    /**
     * Get the status code
     *
     * @return int
     */
    function getStatusCode();

    /**
     * Set the status code
     *
     * When a status code is set, the current optional custom status message should be reset.
     *
     * @param int $code The HTTP status code to use in the response
     * @param string $message A custom message to send in the status line instead of the default
     *                        status messages defined in Imbo\Http\Response\Response.php.
     * @return ResponseInterface
     */
    function setStatusCode($code);

    /**
     * Get the status message
     *
     * If not a custom one has been set, return the default message for the current status code
     *
     * @return string
     */
    function getStatusMessage();

    /**
     * Set the status message if a custom one is needed
     *
     * @param string $message The message to set
     * @return ResponseInterface
     */
    function setStatusMessage($message);

    /**
     * Get the header container
     *
     * @return HeaderContainer
     */
    function getHeaders();

    /**
     * Set the header container
     *
     * @param HeaderContainer $headers Container of headers
     * @return ResponseInterface
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
     * @param ImageInterface|array $content Either an image instance, or an array
     * @return ResponseInterface
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
     * @return ResponseInterface
     */
    function setProtocolVersion($version);

    /**
     * Send the response
     *
     * @param EventInterface $event An event instance
     */
    function send(EventInterface $event);

    /**
     * Prepare the response to send 304 Not Modified to the client
     *
     * @return ResponseInterface
     */
    function setNotModified();

    /**
     * Whether or not the response is an error response
     *
     * @return boolean
     */
    function isError();

    /**
     * Create an error based on an exception instance
     *
     * @param Exception $exception An Imbo\Exception with a fitting HTTP error code and message
     * @param RequestInterface The current request instance
     * @return ResponseInterface
     */
    function createError(Exception $exception, RequestInterface $request);

    /**
     * Populate the current response based on another response
     *
     * @param ResponseInterface $response Another response instance
     * @return ResponseInterface
     */
    function populate(ResponseInterface $response);
}
