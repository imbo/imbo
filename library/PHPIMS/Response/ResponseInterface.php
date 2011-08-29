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

namespace PHPIMS\Response;

use PHPIMS\Image\ImageInterface;

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
    function getCode();

    /**
     * Set the code
     *
     * @param int $code The HTTP status code to use in the response
     * @return PHPIMS\Response\ResponseInterface
     */
    function setCode($code);

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
     * @return PHPIMS\Response\ResponseInterface
     */
    function setHeaders(array $headers);

    /**
     * Set a single header
     *
     * @param string $name The header name
     * @param mixed $value The header value
     * @return PHPIMS\Response\ResponseInterface
     */
    function setHeader($name, $value);

    /**
     * Remove a single header element
     *
     * @param string $name The name of the header. For instance 'Location'
     * @return PHPIMS\Response\ResponseInterface
     */
    function removeHeader($name);

    /**
     * Get the Content-Type
     *
     * @return string
     */
    function getContentType();

    /**
     * Set the Content-Type
     *
     * @param string $type The type to set. For instance "application/json" or "image/png"
     * @return PHPIMS\Response\ResponseInterface
     */
    function setContentType($type);

    /**
     * Get the body
     *
     * @return array
     */
    function getBody();

    /**
     * Set the body
     *
     * @param array $body The body content
     * @return PHPIMS\Response\ResponseInterface
     */
    function setBody(array $body);

    /**
     * Get the image
     *
     * @return PHPIMS\Image\ImageInterface
     */
    function getImage();

    /**
     * Set the image
     *
     * @param PHPIMS\Image\ImageInterface $image The image object
     * @return PHPIMS\Response\ResponseInterface
     */
    function setImage(ImageInterface $image);

    /**
     * See if the response has an image attached to it
     *
     * @return boolean
     */
    function hasImage();
}
