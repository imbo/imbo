<?php
/**
 * Imbo
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
 * @package Imbo
 * @subpackage Interfaces
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */

namespace Imbo\Http\Request;

/**
 * Request interface
 *
 * @package Imbo
 * @subpackage Interfaces
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */
interface RequestInterface {
    /**#@+
     * Supported HTTP methods
     *
     * @var string
     */
    const METHOD_BREW    = 'BREW';
    const METHOD_DELETE  = 'DELETE';
    const METHOD_GET     = 'GET';
    const METHOD_HEAD    = 'HEAD';
    const METHOD_POST    = 'POST';
    const METHOD_PUT     = 'PUT';
    const METHOD_OPTIONS = 'OPTIONS';
    /**#@-*/

    /**
     * Get the public key found in the request
     *
     * @return string
     */
    function getPublicKey();

    /**
     * Set the public key
     *
     * @param string $key The key to set
     * @return Imbo\Http\Request\RequestInterface
     */
    function setPublicKey($key);

    /**
     * Get image transformations from the request
     *
     * @return Imbo\Image\TransformationChain
     */
    function getTransformations();

    /**
     * Get the current accessed path without possible application prefixes
     *
     * @return string
     */
    function getPath();

    /**
     * Get the current resource
     *
     * @return string
     */
    function getResource();

    /**
     * Set the current resource
     *
     * @param string $resource The resource name
     * @return Imbo\Http\Request\RequestInterface
     */
    function setResource($resource);

    /**
     * Get the current image identifier
     *
     * @return string|null
     */
    function getImageIdentifier();

    /**
     * Set the image identifier
     *
     * @param string $imageIdentifier The image identifier to set
     * @return Imbo\Http\Request\RequestInterface
     */
    function setImageIdentifier($imageIdentifier);

    /**
     * Get the current HTTP method
     *
     * Returns one of the constants defined in this interface.
     *
     * @return string
     */
    function getMethod();

    /**
     * Return raw post data
     *
     * @return string
     */
    function getRawData();

    /**
     * Get the query container
     *
     * @return Imbo\Http\ParameterContainerInterface
     */
    function getQuery();

    /**
     * Get the requerst container
     *
     * @return Imbo\Http\ParameterContainer
     */
    function getRequest();

    /**
     * Get the server container
     *
     * @return Imbo\Http\ServerContainerInterface
     */
    function getServer();

    /**
     * Get the HTTP headers
     *
     * @return Imbo\Http\HeaderContainer
     */
    function getHeaders();

    /**
     * Wether or not the request is POST, PUT or DELETE
     *
     * @return boolean
     */
    function isUnsafe();
}
