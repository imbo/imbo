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

namespace Imbo\Http\Request;

/**
 * Request interface
 *
 * @package Interfaces
 * @subpackage Http
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
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
     * Get the private key
     *
     * The private key property is populated by the server based on the public key from the
     * request. The client itself does not place the private key in the request.
     *
     * @return string
     */
    function getPrivateKey();

    /**
     * Set the private key
     *
     * @param string $key The key to set
     * @return Imbo\Http\Request\RequestInterface
     */
    function setPrivateKey($key);

    /**
     * Get image transformations from the request
     *
     * If someone specified a transformation that does not exist, an
     * Imbo\Exception\InvalidArgumentException exception must be thrown.
     *
     * @throws Imbo\Exception\InvalidArgumentException
     * @return Imbo\Image\TransformationChain
     */
    function getTransformations();

    /**
     * Get the current scheme (http or https)
     *
     * @return string
     */
    function getScheme();

    /**
     * Get the host, without port number
     *
     * @return string
     */
    function getHost();

    /**
     * Get the port
     *
     * @return int
     */
    function getPort();

    /**
     * Get the base URL
     *
     * @return string
     */
    function getBaseUrl();

    /**
     * Get the current accessed path (the part after the base URL)
     *
     * @return string
     */
    function getPath();

    /**
     * Get the current URL including query parameters
     *
     * If the accessed port equals 80 and the scheme is HTTP or if the accessed port is 443 and the
     * scheme is https, the port must be stripped from the URL returned from this method.
     *
     * @return string
     */
    function getUrl();

    /**
     * Get the image identifier from the URL
     *
     * Note that this might not be valid for the current image data since event listeners have the
     * possibility to change the image data before the imbo application handles it. The method
     * called getRealImageIdentifier() returns the identifier of the image data currently stored in
     * the request instance.
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
     * Get the image identifier from the image data stored in the request instance
     *
     * This method returns null if there is no stored raw data.
     *
     * @return string|null
     */
    function getRealImageIdentifier();

    /**
     * Get the current image extension (if any)
     *
     * @return string|null
     */
    function getImageExtension();

    /**
     * Set the image extension
     *
     * @param string $extension The image extension to set
     * @return Imbo\Http\Request\RequestInterface
     */
    function setImageExtension($extension);

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
     * Set the raw data
     *
     * This method must also update the current imageIdentifier
     *
     * @param string $data The data to set
     * @return Imbo\Http\Request\RequestInterface
     */
    function setRawData($data);

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
