<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Http\Request;

use Imbo\Exception\InvalidArgumentException,
    Imbo\Image\Image;

/**
 * Request interface
 *
 * @package Interfaces
 * @subpackage Http
 * @author Christer Edvartsen <cogo@starzinger.net>
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
     * @return RequestInterface
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
     * @return RequestInterface
     */
    function setPrivateKey($key);

    /**
     * Get image transformations from the request
     *
     * @return array
     */
    function getTransformations();

    /**
     * Check whether or not the request includes image transformations
     *
     * @return boolean
     */
    function hasTransformations();

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
     * @return string|null
     */
    function getImageIdentifier();

    /**
     * Set the image identifier
     *
     * @param string $imageIdentifier The image identifier to set
     * @return RequestInterface
     */
    function setImageIdentifier($imageIdentifier);

    /**
     * Get the current requested extension (if any)
     *
     * @return string|null
     */
    function getExtension();

    /**
     * Set the extension requested
     *
     * @param string $extension The extension to set
     * @return RequestInterface
     */
    function setExtension($extension);

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
     * @param string $data The data to set
     * @return RequestInterface
     */
    function setRawData($data);

    /**
     * Get the query container
     *
     * @return ParameterContainer
     */
    function getQuery();

    /**
     * Get the requerst container
     *
     * @return ParameterContainer
     */
    function getRequest();

    /**
     * Get the server container
     *
     * @return ServerContainer
     */
    function getServer();

    /**
     * Get the HTTP headers
     *
     * @return HeaderContainer
     */
    function getHeaders();

    /**
     * Whether or not the request is POST, PUT or DELETE
     *
     * @return boolean
     */
    function isUnsafe();

    /**
     * Split Accept-* headers
     *
     * @param string $header The header string to split, for instance "audio/*; q=0.2, audio/basic"
     * @return array Returns an array with the media type as keys and the quality as values
     */
    function splitAcceptHeader($header);

    /**
     * Get the acceptable content types for the current request
     *
     * This method will return an array where the keys are the acceptable mime types, and the
     * values are the quality associated with the mime types
     *
     * @return array
     */
    function getAcceptableContentTypes();

    /**
     * Set the resource name (one of the constants defined in Imbo\Resource\ResourceInterface)
     *
     * @param string $resource The name of the resource
     * @return RequestInterface
     */
    function setResource($resource);

    /**
     * Get the resource name
     *
     * @return string
     */
    function getResource();

    /**
     * Set an image instance
     *
     * @param Image $image An image instance
     * @return RequestInterface
     */
    function setImage(Image $image);

    /**
     * Get an image instance
     *
     * @return null|Image
     */
    function getImage();
}
