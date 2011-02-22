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
 * @subpackage Client
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

/**
 * Client that interacts with the server part of PHPIMS
 *
 * This client includes methods that can be used to easily interact with a PHPIMS server
 *
 * @package PHPIMS
 * @subpackage Client
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class PHPIMS_Client {
    /**
     * The server URL
     *
     * @var string
     */
    protected $serverUrl = null;

    /**
     * The cURL handle used by the client
     *
     * @var resource
     */
    protected $curlHandle = null;

    /**
     * Default timeout
     *
     * @var int
     */
    protected $timeout = 2;

    /**
     * Default connection timeout
     *
     * @var int
     */
    protected $connectTimeout = 2;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->curlHandle = curl_init();

        curl_setopt_array($this->curlHandle, array(
            CURLOPT_USERAGENT      => get_class($this),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_HTTPHEADER     => array('Expect:'),
        ));
    }

    /**
     * Class destructor
     */
    public function __destruct() {
        curl_close($this->curlHandle);
    }

    /**
     * Get the server URL
     *
     * @return string
     */
    public function getServerUrl() {
        return $this->serverUrl;
    }

    /**
     * Set the server url
     *
     * @param string $url The URL to set
     * @return PHPIMS_Client
     */
    public function setServerUrl($url) {
        $this->serverUrl = $url;

        return $this;
    }

    /**
     * Get the timeout
     *
     * @return int
     */
    public function getTimeout() {
        return $this->timeout;
    }

    /**
     * Set the timeout
     *
     * @param int $timeout Timeout in seconds
     * @return VGF_RemoteContent
     */
    public function setTimeout($timeout) {
        $this->timeout = (int) $timeout;

        return $this;
    }

    /**
     * Get the connection timeout
     *
     * @return int
     */
    public function getConnectTimeout() {
        return $this->connectTimeout;
    }

    /**
     * Set the connection timeout
     *
     * @param int $connectTimeout Timeout in seconds
     * @return VGF_RemoteContent
     */
    public function setConnectTimeout($connectTimeout) {
        $this->connectTimeout = $connectTimeout;

        return $this;
    }

    /**
     * Fetch metadata about an image
     *
     * @return PHPIMS_Image_Metadata_Collection
     */
    public function getMetaData() {

    }

    /**
     * Get an image
     *
     * @param string $imageId The image identifier
     * @return PHPIMS_Image
     */
    public function get($imageId) {

    }

    /**
     * Add a new image to the server
     *
     * @param string $path Path to the local image
     * @param PHPIMS_Image_Metadata_Collection $metadata Metadata to attach to the image
     * @return array Returns an array with status information about the request. The resulting
     *               image identifier will be included in the response. This identification must be
     *               used for other operations regarding the image.
     */
    public function add($path, PHPIMS_Image_Metadata_Collection $metadata) {

    }

    /**
     * Delete an image from the server
     *
     * @param string $imageId Image identifier
     * @return array Returne an array with status information about the request
     */
    public function delete($imageId) {

    }

    /**
     * Edit an image
     *
     * @param string $imageId The image identifier
     * @param PHPIMS_Image_Metadata_Collection $metadata Metadata to connect to the image
     * @param boolean $replace Wether or not to replace existing metadata with the $metadata
     *                         parameter
     * @return array Returne an array with status information about the request
     */
    public function edit($imageId, PHPIMS_Image_Metadata_Collection $metadata, $replace = false) {

    }
}