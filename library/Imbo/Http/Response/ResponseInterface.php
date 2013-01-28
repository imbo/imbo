<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Http\Response;

use Imbo\Http\HeaderContainer,
    Imbo\Exception,
    Imbo\Http\Request\RequestInterface,
    Imbo\Image\Image,
    Imbo\Model\ModelInterface;

/**
 * Response interface
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Http
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
     * When a status code is set, the current optional custom status message should be reset.
     *
     * @param int $code The HTTP status code to use in the response
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
     * @param string $message A custom message to send in the status line instead of the default
     *                        status messages defined in Imbo\Http\Response\Response.
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
     * @param Image|array $content Either an image instance, or an array
     * @return ResponseInterface
     */
    function setBody($content);

    /**
     * Get the model instance
     *
     * @return null|ModelInterface
     */
    function getModel();

    /**
     * Set the model instance
     *
     * @param ModelInterface $model A model instance
     * @return ResponseInterface
     */
    function setModel(ModelInterface $model);

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
     * Get the image instance
     *
     * @return Image
     */
    function getImage();

    /**
     * Set an image instance
     *
     * @param Image $image An image instance
     * @return ResponseInterface
     */
    function setImage(Image $image);

    /**
     * Prepare the response to send 304 Not Modified to the client
     *
     * @return ResponseInterface
     */
    function setNotModified();

    /**
     * Fetch the Last-Modified header
     *
     * @return string
     */
    function getLastModified();

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
}
