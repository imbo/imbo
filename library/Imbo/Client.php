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
 * @subpackage Client
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */

namespace Imbo;

use Imbo\Client\Driver\DriverInterface;
use Imbo\Client\ImageUrl;
use Imbo\Client\Driver\Curl as DefaultDriver;
use Imbo\Client\Exception as ClientException;
use Imbo\Image\TransformationChain;

/**
 * Client that interacts with the server part of Imbo
 *
 * This client includes methods that can be used to easily interact with a Imbo server. All
 * requests made by the client goes through a driver.
 *
 * @package Imbo
 * @subpackage Client
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */
class Client {
    /**
     * The server URL
     *
     * @var string
     */
    private $serverUrl;

    /**
     * Driver used by the client
     *
     * @var Imbo\Client\Driver\DriverInterface
     */
    private $driver;

    /**
     * Public key used for signed requests
     *
     * @var string
     */
    private $publicKey;

    /**
     * Private key used for signed requests
     *
     * @var string
     */
    private $privateKey;

    /**
     * Class constructor
     *
     * @param string $serverUrl The URL to the Imbo server, including protocol
     * @param string $publicKey The public key to use
     * @param string $privateKey The private key to use
     * @param Imbo\Client\Driver\DriverInterface $driver Optional driver to set
     */
    public function __construct($serverUrl, $publicKey, $privateKey, DriverInterface $driver = null) {
        $this->serverUrl  = rtrim($serverUrl, '/');
        $this->publicKey  = $publicKey;
        $this->privateKey = $privateKey;

        if ($driver === null) {
            // @codeCoverageIgnoreStart
            $driver = new DefaultDriver();
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
    private function getResourceUrl($resourceIdentifier) {
        return $this->serverUrl . '/' . $this->publicKey . '/' . $resourceIdentifier;
    }

    /**
     * Generate an MD5 image identifier for a given file
     *
     * @param string $path Path to the local image
     * @return string
     * @throws Imbo\Client\Exception
     */
    private function getImageIdentifier($path) {
        if (!is_file($path)) {
            throw new ClientException('File does not exist: ' . $path);
        }

        // Get file extension
        $info = getimagesize($path);

        if (!$info) {
            throw new ClientException('Unknown image format of file: ' . $path);
        }

        $extension = image_type_to_extension($info[2], false);

        // Generate MD5 sum of the file
        return md5_file($path) . '.' . $extension;
    }

    /**
     * Add a new image to the server
     *
     * This method will first PUT the image on the server, and then POST the metadata if the PUT
     * was successful.
     *
     * @param string $path Path to the local image
     * @param array $metadata Metadata to attach to the image
     * @return Imbo\Client\Response
     */
    public function addImage($path, array $metadata = null) {
        $imageIdentifier = $this->getImageIdentifier($path);

        $url = $this->getSignedResourceUrl(DriverInterface::PUT, $imageIdentifier);

        // Add the image, and then POST metadata
        $response = $this->driver->put($url, $path);

        if ($response->isSuccess()) {
            // Add metadata
            if ($metadata === null) {
                $metadata = array();
            }

            $metadata['filename'] = basename($path);

            $response = $this->editMetadata($imageIdentifier, $metadata);
        }

        return $response;
    }

    /**
     * Delete an image from the server
     *
     * @param string $imageIdentifier The image identifier
     * @return Imbo\Client\Response
     */
    public function deleteImage($imageIdentifier) {
        $url = $this->getSignedResourceUrl(DriverInterface::DELETE, $imageIdentifier);

        return $this->driver->delete($url);
    }

    /**
     * Edit an image
     *
     * @param string $imageIdentifier The image identifier
     * @param array $metadata An array of metadata
     * @return Imbo\Client\Response
     */
    public function editMetadata($imageIdentifier, array $metadata) {
        $url = $this->getSignedResourceUrl(DriverInterface::POST, $imageIdentifier . '/meta');

        return $this->driver->post($url, $metadata);
    }

    /**
     * Delete metadata
     *
     * @param string $imageIdentifier The image identifier
     * @return Imbo\Client\Response
     */
    public function deleteMetadata($imageIdentifier) {
        $url = $this->getSignedResourceUrl(DriverInterface::DELETE, $imageIdentifier . '/meta');

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
    private function generateSignature($method, $resourceIdentifier, $timestamp) {
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
    private function getSignedResourceUrl($method, $resourceIdentifier) {
        $timestamp = gmdate('Y-m-d\TH:i\Z');
        $signature = $this->generateSignature($method, $resourceIdentifier, $timestamp);

        $url = $this->getResourceUrl($resourceIdentifier)
             . sprintf('?signature=%s&timestamp=%s', rawurlencode($signature), rawurlencode($timestamp));

        return $url;
    }
}
