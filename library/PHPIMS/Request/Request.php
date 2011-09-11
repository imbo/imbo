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
 * @subpackage Request
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Request;

use PHPIMS\Image\Transformation;
use PHPIMS\Image\TransformationChain;

/**
 * Request class
 *
 * @package PHPIMS
 * @subpackage Request
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class Request implements RequestInterface {
    /**
     * Valid HTTP methods
     *
     * @var array
     */
    private $validMethods = array(
        RequestInterface::METHOD_GET     => true,
        RequestInterface::METHOD_POST    => true,
        RequestInterface::METHOD_PUT     => true,
        RequestInterface::METHOD_HEAD    => true,
        RequestInterface::METHOD_DELETE  => true,
        RequestInterface::METHOD_BREW    => true,
        RequestInterface::METHOD_OPTIONS => true,
    );

    /**
     * Public key from the url
     *
     * @var string
     */
    private $publicKey;

    /**
     * Private key from the server configuration
     *
     * @var string
     */
    private $privateKey;

    /**
     * Resource name from the url
     *
     * @var string
     */
    private $resouce;

    /**
     * Image identifier from the url
     *
     * @var string
     */
    private $imageIdentifier;

    /**
     * The HTTP method
     *
     * Should be one of the defined constants in PHPIMS\Request\RequestInterface
     *
     * @var string
     */
    private $method;

    /**
     * Type of the request (one of the TYPE constants defined in this class)
     *
     * @var int
     */
    private $type;

    /**
     * Class constructor
     *
     * @param string $method The HTTP method used
     * @param string $query The current query
     * @param array $authConfig Authentication part of the PHPIMS server configuration array
     * @throws PHPIMS\Request\Exception
     */
    public function __construct($method, $query, array $authConfig) {
        $method = strtoupper($method);

        if (!isset($this->validMethods[$method])) {
            throw new Exception('Unsupported HTTP method: ' . $method, 501);
        }

        $this->method = $method;

        $parts = parse_url($query);
        $path = trim($parts['path'], '/');

        $matches  = array();

        if (!preg_match('#^(?<publicKey>[a-f0-9]{32})/(?<resource>(images|(?<imageIdentifier>[a-f0-9]{32}\.[a-zA-Z]{3,4})(?:/(?<metadata>meta))?))$#', $path, $matches)) {
            throw new Exception('Unknown resource: ' . $query, 400);
        }

        $this->resource = $matches['resource'];
        $this->publicKey = $matches['publicKey'];
        $this->imageIdentifier = isset($matches['imageIdentifier']) ? $matches['imageIdentifier'] : null;

        // Make sure we have a valid public and private key pair
        if (!isset($authConfig[$this->publicKey])) {
            throw new Exception('Unknown public key', 400);
        }

        $this->privateKey = $authConfig[$this->publicKey];

        // Decide the type of the request
        if (isset($matches['imageIdentifier']) && isset($matches['metadata'])) {
            $this->type = RequestInterface::RESOURCE_METADATA;
        } else if (isset($matches['imageIdentifier'])) {
            $this->type = RequestInterface::RESOURCE_IMAGE;
        } else {
            $this->type = RequestInterface::RESOURCE_IMAGES;
        }
    }

    /**
     * @see PHPIMS\Request\RequestInterface::getPublicKey()
     */
    public function getPublicKey() {
        return $this->publicKey;
    }

    /**
     * @see PHPIMS\Request\RequestInterface::getPrivateKey()
     */
    public function getPrivateKey() {
        return $this->privateKey;
    }

    /**
     * @see PHPIMS\Request\RequestInterface::getTransformations()
     */
    public function getTransformations() {
        $transformations = $this->get('t');
        $chain = new TransformationChain();

        if (!is_array($transformations)) {
            return $chain;
        }

        foreach ($transformations as $transformation) {
            // See if the transformation has any parameters
            $pos = strpos($transformation, ':');
            $urlParams = '';

            if ($pos === false) {
                // No params exist
                $name = $transformation;
            } else {
                list($name, $urlParams) = explode(':', $transformation, 2);
            }

            // Initialize params for the transformation
            $params = array();

            // See if we have more than one parameter
            if (strpos($urlParams, ',') !== false) {
                $urlParams = explode(',', $urlParams);
            } else {
                $urlParams = array($urlParams);
            }

            foreach ($urlParams as $param) {
                $pos = strpos($param, '=');

                if ($pos !== false) {
                    $params[substr($param, 0, $pos)] = substr($param, $pos + 1);
                }
            }

            // Closure to help fetch parameters
            $p = function($key) use ($params) {
                return isset($params[$key]) ? $params[$key] : null;
            };

            if ($name === 'border') {
                $chain->add(new Transformation\Border($p('color'), $p('width'), $p('height')));
            } else if ($name === 'compress') {
                $chain->add(new Transformation\Compress($p('quality')));
            } else if ($name === 'crop') {
                $chain->add(new Transformation\Crop($p('x'), $p('y'), $p('width'), $p('height')));
            } else if ($name === 'flipHorizontally') {
                $chain->add(new Transformation\FlipHorizontally());
            } else if ($name === 'flipVertically') {
                $chain->add(new Transformation\FlipVertically());
            } else if ($name === 'resize') {
                $chain->add(new Transformation\Resize($p('width'), $p('height')));
            } else if ($name === 'rotate') {
                $chain->add(new Transformation\Rotate($p('angle'), $p('bg')));
            } else if ($name === 'thumbnail') {
                $chain->add(new Transformation\Thumbnail($p('width'), $p('height'), $p('fit')));
            }
        }

        return $chain;
    }

    /**
     * @see PHPIMS\Request\RequestInterface::getResource()
     */
    public function getResource() {
        return $this->resource;
    }

    /**
     * @see PHPIMS\Request\RequestInterface::getImageIdentifier()
     */
    public function getImageIdentifier() {
        return $this->imageIdentifier;
    }

    /**
     * @see PHPIMS\Request\RequestInterface::setImageIdentifier()
     */
    public function setImageIdentifier($imageIdentifier) {
        $this->imageIdentifier = $imageIdentifier;

        return $this;
    }

    /**
     * @see PHPIMS\Request\RequestInterface::getMethod()
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * @see PHPIMS\Request\RequestInterface::getTimestamp()
     */
    public function getTimestamp() {
        return $this->get('timestamp');
    }

    /**
     * @see PHPIMS\Request\RequestInterface::getSignature()
     */
    public function getSignature() {
        return $this->get('signature');
    }

    /**
     * @see PHPIMS\Request\RequestInterface::getMetadata()
     */
    public function getMetadata() {
        $metadata = $this->getPost('metadata');

        if (!$metadata) {
            return null;
        }

        return json_decode($metadata, true);
    }

    /**
     * @see PHPIMS\Request\RequestInterface::getPost()
     */
    public function getPost($key) {
        return $this->hasPost($key) ? $_POST[$key] : null;
    }

    /**
     * @see PHPIMS\Request\RequestInterface::hasPost()
     */
    public function hasPost($key) {
        return isset($_POST[$key]);
    }

    /**
     * @see PHPIMS\Request\RequestInterface::get()
     */
    public function get($key) {
        return $this->has($key) ? $_GET[$key] : null;
    }

    /**
     * @see PHPIMS\Request\RequestInterface::has()
     */
    public function has($key) {
        return isset($_GET[$key]);
    }

    /**
     * @see PHPIMS\Request\RequestInterface::isMetadataRequest()
     */
    public function isMetadataRequest() {
        return $this->type === RequestInterface::RESOURCE_METADATA;
    }

    /**
     * @see PHPIMS\Request\RequestInterface::isImageRequest()
     */
    public function isImageRequest() {
        return $this->type === RequestInterface::RESOURCE_IMAGE;
    }

    /**
     * @see PHPIMS\Request\RequestInterface::isImagesRequest()
     */
    public function isImagesRequest() {
        return $this->type === RequestInterface::RESOURCE_IMAGES;
    }

    /**
     * @see PHPIMS\Request\RequestInterface::getRawData()
     * @codeCoverageIgnore
     */
    public function getRawData() {
        return file_get_contents('php://input');
    }
}
