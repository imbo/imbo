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
 * This client includes methods that can be used to easily interact with a PHPIMS server. All
 * requests made by the client goes through a driver.
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
     * Driver used for the client
     *
     * @var PHPIMS_Client_Driver_Abstract
     */
    protected $driver = null;

    /**
     * Class constructor
     *
     * @param PHPIMS_Client_Driver_Abstract $driver Optional driver to set
     */
    public function __construct(PHPIMS_Client_Driver_Abstract $driver = null) {
        if ($driver !== null) {
            $this->setDriver($driver);
        }
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
     * Get the current driver
     *
     * @return PHPIMS_Client_Driver_Abstract
     */
    public function getDriver() {
        return $this->driver;
    }

    /**
     * Set the driver
     *
     * @param PHPIMS_Client_Driver_Abstract $driver A driver instance
     * @return PHPIMS_Client
     */
    public function setDriver(PHPIMS_Client_Driver_Abstract $driver) {
        $driver->setClient($this);
        $this->driver = $driver;

        return $this;
    }

    /**
     * Add a new image to the server
     *
     * @param string $path Path to the local image
     * @param array $metadata Metadata to attach to the image
     * @return PHPIMS_Client_Response
     * @throws PHPIMS_Client_Exception
     */
    public function addImage($path, array $metadata = null) {
        if (!is_file($path)) {
            throw new PHPIMS_Client_Exception('File does not exist: ' . $path);
        }

        if ($metadata !== null) {
            $data = $metadata;
        } else {
            $data = array();
        }

        // Add the file reference
        $data['file'] = '@' . $path;

        return $this->getDriver()->post($data, $this->serverUrl);
    }

    /**
     * Delete an image from the server
     *
     * @param string $imageId Image identifier
     * @return array Returne an array with status information about the request
     */
    public function deleteImage($imageId) {
        return $this->getDriver()->delete($this->serverUrl . '/' . $imageId);
    }

    /**
     * Edit an image
     *
     * @param string $imageId The image identifier
     * @param array $metadata An array of metadata
     * @return array Returns an array with status information about the request
     */
    public function editMetadata($imageId, array $metadata) {
        return $this->getDriver()->post($metadata, $this->serverUrl . '/' . $imageId);
    }

    /**
     * Get metadata
     *
     * @param string $imageId The image identifier
     * @return array Returns an array with metadata
     */
    public function getMetadata($imageId) {
        return $this->getDriver()->get($this->serverUrl . '/' . $imageId . '/meta');
    }
}