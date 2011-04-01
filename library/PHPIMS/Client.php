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
     * Public key
     *
     * @var string
     */
    protected $publicKey = null;

    /**
     * Private key
     *
     * @var string
     */
    protected $privateKey = null;

    /**
     * Class constructor
     *
     * @param string $serverUrl The URL to the PHPIMS server, including protocol
     * @param string $publicKey The public key to use. Only some operations need this
     * @param string $privateKey The private key to use. Only some operations need this
     * @param PHPIMS_Client_Driver_Abstract $driver Optional driver to set
     */
    public function __construct($serverUrl, $publicKey = null, $privateKey = null, PHPIMS_Client_Driver_Abstract $driver = null) {
        $this->setServerUrl($serverUrl);

        if ($publicKey !== null) {
            $this->setPublicKey($publicKey);
        }

        if ($privateKey !== null) {
            $this->setPrivateKey($privateKey);
        }

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
     * Get the server path for a hash
     *
     * @param string $hash The image hash
     * @return string
     */
    public function getServerPath($hash) {
        return $this->getServerUrl() . '/' . $hash;
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
        if ($this->driver === null) {
            // @codeCoverageIgnoreStart
            $this->driver = new PHPIMS_Client_Driver_Curl();
            $this->driver->setClient($this);
        }
        // @codeCoverageIgnoreEnd

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

        // Get extension
        $info = getimagesize($path);
        $extension = image_type_to_extension($info[2], false);

        // Generate MD5
        $hash = md5_file($path) . '.' . $extension;

        $url = $this->getSignedUrl('POST', $hash);

        return $this->getDriver()->addImage($path, $url, $metadata);
    }

    /**
     * Delete an image from the server
     *
     * @param string $hash The image identifier
     * @return PHPIMS_Client_Response
     */
    public function deleteImage($hash) {
        $url = $this->getSignedUrl('DELETE', $hash);

        return $this->getDriver()->delete($url);
    }

    /**
     * Edit an image
     *
     * @param string $hash The image identifier
     * @param array $metadata An array of metadata
     * @return PHPIMS_Client_Response
     */
    public function editMetadata($hash, array $metadata) {
        $url = $this->getSignedUrl('POST', $hash . '/meta');

        return $this->getDriver()->post($url, $metadata);
    }

    /**
     * Delete metadata
     *
     * @param string $hash The image identifier
     * @return PHPIMS_Client_Response
     */
    public function deleteMetadata($hash) {
        $url = $this->getSignedUrl('DELETE', $hash . '/meta');

        return $this->getDriver()->delete($url);
    }

    /**
     * Get metadata
     *
     * @param string $hash The image identifier
     * @return array Returns an array with metadata
     */
    public function getMetadata($hash) {
        return $this->getDriver()->get($this->getServerPath($hash . '/meta'));
    }

    /**
     * Get the public key
     *
     * @return string
     */
    public function getPublicKey() {
        return $this->publicKey;
    }

    /**
     * Set the public key
     *
     * @param string $key The key to set
     * @return PHPIMS_Client
     */
    public function setPublicKey($key) {
        $this->publicKey = $key;

        return $this;
    }

    /**
     * Get the private key
     *
     * @return string
     */
    public function getPrivateKey() {
        return $this->privateKey;
    }

    /**
     * Set the private key
     *
     * @param string $key The key to set
     * @return PHPIMS_Client
     */
    public function setPrivateKey($key) {
        $this->privateKey = $key;

        return $this;
    }

    /**
     * Generate a signature that can be sent to the server
     *
     * @param string $method HTTP method (POST or DELETE)
     * @param string $path The path requested (for instance "<hash>/meta")
     * @param string $timestamp GMT timestamp
     * @return string
     */
    protected function generateSignature($method, $path, $timestamp) {
        $data = $method . $path . $this->getPublicKey() . $timestamp;

        // Generate binary hash key
        $hash = hash_hmac('sha256', $data, $this->getPrivateKey(), true);

        // Generate signature for the request
        $signature = base64_encode($hash);

        return $signature;
    }

    /**
     * Get a signed url
     *
     * @param string $method HTTP method
     * @param string $path The path we want to request
     * @return string Returns a string with the necessary parts for authenticating
     */
    protected function getSignedUrl($method, $hash) {
        $timestamp = gmdate('Y-m-d\TH:i\Z');
        $signature = $this->generateSignature($method, $hash, $timestamp);

        $url = $this->getServerPath($hash)
             . sprintf('?signature=%s&publicKey=%s&timestamp=%s', rawurlencode($signature), $this->getPublicKey(), rawurlencode($timestamp));

        return $url;
    }
}