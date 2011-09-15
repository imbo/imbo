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

namespace PHPIMS\Http\Request;

/**
 * Request interface
 *
 * @package PHPIMS
 * @subpackage Interfaces
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
interface RequestInterface {
    /**#@+
     * Supported resource types
     *
     * @var int
     */
    const RESOURCE_IMAGE    = 'IMAGE';
    const RESOURCE_IMAGES   = 'IMAGES';
    const RESOURCE_METADATA = 'METADATA';
    const RESOURCE_UNKNOWN  = 'UNKNOWN';
    /**#@-*/

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
     * Get image transformations from the request
     *
     * @return PHPIMS\Image\TransformationChain
     */
    function getTransformations();

    /**
     * Get the current resource
     *
     * @return string
     */
    function getResource();

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
     * @return PHPIMS\Http\Request\RequestInterface
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
     * Get the request type
     *
     * @return string
     */
    function getType();

    /**
     * Get the query container
     *
     * @return PHPIMS\Http\ParameterContainerInterface
     */
    function getQuery();

    /**
     * Wether or not the request is POST, PUT or DELETE
     *
     * @return boolean
     */
    function isUnsafe();
}
