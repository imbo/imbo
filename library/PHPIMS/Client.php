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

namespace PHPIMS;

use PHPIMS\Client\DriverInterface;
use PHPIMS\Client\ImageUrl;
use PHPIMS\Client\Driver\Curl as DefaultDriver;
use PHPIMS\Client\Exception as ClientException;
use PHPIMS\Image\TransformationChain;

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
class Client {
    /**
     * The server URL
     *
     * @var string
     */
    private $serverUrl = null;

    /**
     * Driver used by the client
     *
     * @var PHPIMS\Client\DriverInterface
     */
    private $driver = null;

    /**
     * Public key used for signed requests
     *
     * @var string
     */
    private $publicKey = null;

    /**
     * Private key used for signed requests
     *
     * @var string
     */
    private $privateKey = null;

    /**
     * Class constructor
     *
     * @param string $serverUrl The URL to the PHPIMS server, including protocol
     * @param string $publicKey The public key to use
     * @param string $privateKey The private key to use
     * @param PHPIMS\Client\DriverInterface $driver Optional driver to set
     */
    public function __construct($serverUrl, $publicKey, $privateKey, DriverInterface $driver = null) {
        $this->serverUrl  = $serverUrl;
        $this->publicKey  = $publicKey;
        $this->privateKey = $privateKey;

        if ($driver === null) {
            // @codeCoverageIgnoreStart
            $driver = new DefaultDriver;
        }
        // @codeCoverageIgnoreEnd

        $this->driver = $driver;
    }

    /**
     * Get the complete url for a resource
     *
     * @param string $resourceIdentifier The resource identifier. For instance "<md5>.png" or
     *                                   "<md5>.png/meta"
     * @return string
     */
    public function getResourceUrl($resourceIdentifier) {
        return $this->serverUrl . '/' . $resourceIdentifier;
    }

    /**
     * Generate an MD5 image identifier for a given file
     *
     * @param string $path Path to the local image
     * @return string
     * @throws PHPIMS\Client\Exception
     */
    public function getImageIdentifier($path) {
        if (!is_file($path)) {
            throw new ClientException('File does not exist: ' . $path);
        }

        // Get file extension
        $info = getimagesize($path);
        $extension = image_type_to_extension($info[2], false);

        // Generate MD5 sum of the file
        return md5_file($path) . '.' . $extension;
    }

    /**
     * Add a new image to the server
     *
     * @param string $path Path to the local image
     * @param array $metadata Metadata to attach to the image
     * @return PHPIMS\Client\Response
     */
    public function addImage($path, array $metadata = null) {
        $imageIdentifier = $this->getImageIdentifier($path);

        $url = $this->getSignedResourceUrl('POST', $imageIdentifier);

        return $this->driver->addImage($path, $url, $metadata);
    }

    /**
     * Delete an image from the server
     *
     * @param string $imageIdentifier The image identifier
     * @return PHPIMS\Client\Response
     */
    public function deleteImage($imageIdentifier) {
        $url = $this->getSignedResourceUrl('DELETE', $imageIdentifier);

        return $this->driver->delete($url);
    }

    /**
     * Edit an image
     *
     * @param string $imageIdentifier The image identifier
     * @param array $metadata An array of metadata
     * @return PHPIMS\Client\Response
     */
    public function editMetadata($imageIdentifier, array $metadata) {
        $url = $this->getSignedResourceUrl('POST', $imageIdentifier . '/meta');

        return $this->driver->post($url, $metadata);
    }

    /**
     * Delete metadata
     *
     * @param string $imageIdentifier The image identifier
     * @return PHPIMS\Client\Response
     */
    public function deleteMetadata($imageIdentifier) {
        $url = $this->getSignedResourceUrl('DELETE', $imageIdentifier . '/meta');

        return $this->driver->delete($url);
    }

    /**
     * Get image metadata
     *
     * @param string $imageIdentifier The image identifier
     * @return array Returns an array with metadata
     */
    public function getMetadata($imageIdentifier) {
        $url = $this->getResourceUrl($imageIdentifier . '/meta');

        return $this->driver->get($url);
    }

    /**
     * Generate a signature that can be sent to the server
     *
     * @param string $method HTTP method (POST or DELETE)
     * @param string $resourceIdentifier The resource identifier (for instance "<image>/meta")
     * @param string $timestamp GMT timestamp
     * @return string
     */
    public function generateSignature($method, $resourceIdentifier, $timestamp) {
        $data = $method . $resourceIdentifier . $this->publicKey . $timestamp;

        // Generate binary hash key
        $hash = hash_hmac('sha256', $data, $this->privateKey, true);

        // Encode signature for the request
        $signature = base64_encode($hash);

        return $signature;
    }

    /**
     * Get a signed url
     *
     * @param string $method HTTP method
     * @param string $resourceIdentifier The resource identifier (for instance "<image>/meta")
     * @return string Returns a string with the necessary parts for authenticating
     */
    public function getSignedResourceUrl($method, $resourceIdentifier) {
        $timestamp = gmdate('Y-m-d\TH:i\Z');
        $signature = $this->generateSignature($method, $resourceIdentifier, $timestamp);

        $url = $this->getResourceUrl($resourceIdentifier)
             . sprintf('?signature=%s&publicKey=%s&timestamp=%s', rawurlencode($signature), $this->publicKey, rawurlencode($timestamp));

        return $url;
    }

    /**
     * Get url to an image
     *
     * @param string $imageIdentifier Image identifier
     * @param PHPIMS\Image\TransformationChain $transformationChain An optional chain of
     *                                                              transformations
     * @return PHPIMS\Client\ImageUrl
     */
    public function getImageUrl($imageIdentifier, TransformationChain $transformationChain = null) {
        $url = $this->getResourceUrl($imageIdentifier);
        $imageUrl = new ImageUrl($url);

        if ($transformationChain !== null) {
            $transformationChain->applyToImageUrl($imageUrl);
        }

        return $imageUrl;
    }
}