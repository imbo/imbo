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
 * @subpackage Server
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS;

use PHPIMS\Response\Response;
use PHPIMS\Operation;
use PHPIMS\Image\Image;

/**
 * Client that interacts with the server part of PHPIMS
 *
 * This client includes methods that can be used to easily interact with a PHPIMS server
 *
 * @package PHPIMS
 * @subpackage Server
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class FrontController {
    /**#@+
     * Supported HTTP methods
     *
     * @var string
     */
    const GET    = 'GET';
    const POST   = 'POST';
    const PUT    = 'PUT';
    const HEAD   = 'HEAD';
    const DELETE = 'DELETE';
    const BREW   = 'BREW';
    /**#@-*/

    /**
     * Valid HTTP methods
     *
     * @var array
     */
    private $validMethods = array(
        self::GET    => true,
        self::POST   => true,
        self::PUT    => true,
        self::HEAD   => true,
        self::DELETE => true,
        self::BREW   => true,
    );

    /**
     * Configuration
     *
     * @var array
     */
    private $config;

    /**
     * Class constructor
     *
     * @param array $config Configuration array
     */
    public function __construct(array $config) {
        $this->config = $config;
    }

    /**
     * Wether or not the method is valid
     *
     * @param string $method The current method
     * @return boolean True if $method is valid, false otherwise
     */
    public function isValidMethod($method) {
        return isset($this->validMethods[$method]);
    }

    /**
     * Generate an operation object based on some parameters
     *
     * @param string $resource The accessed resource
     * @param string $method The HTTP method
     * @param string $imageIdentifier Optional Image identifier
     * @param string $extra Optional extra argument
     * @throws PHPIMS\Exception
     * @return PHPIMS\OperationInterface
     */
    private function resolveOperation($resource, $method, $imageIdentifier = null, $extra = null) {
        $operation = null;

        if ($resource === 'images' && $method === self::GET) {
            $operation = new Operation\GetImages();
        } else if ($method === self::GET && $imageIdentifier) {
            if ($extra === 'meta') {
                $operation = new Operation\GetImageMetadata();
            } else {
                $operation = new Operation\GetImage();
            }
        } else if ($method === self::POST && $imageIdentifier && $extra === 'meta') {
            $operation = new Operation\EditImageMetadata();
        } else if ($method === self::PUT && $imageIdentifier) {
            $operation = new Operation\AddImage();
        } else if ($method === self::DELETE && $imageIdentifier) {
            if ($extra === 'meta') {
                $operation = new Operation\DeleteImageMetadata();
            } else {
                $operation = new Operation\DeleteImage();
            }
        } else if ($method === self::HEAD && $imageIdentifier) {
            if ($extra === 'meta') {
                // Not yet implemented
            } else {
                $operation = new Operation\HeadImage();
            }
        } else if ($method === self::BREW) {
            throw new Exception('I\'m a teapot!', 418);
        }

        if ($operation === null) {
            throw new Exception('Unsupported operation', 400);
        }

        // Create the operation
        $operation->setDatabase($this->config['database'])
                  ->setStorage($this->config['storage'])
                  ->setConfig($this->config)
                  ->setResource($resource)
                  ->setImageIdentifier($imageIdentifier)
                  ->setMethod($method)
                  ->setImage(new Image())
                  ->setResponse(new Response());

        return $operation;
    }

    /**
     * Handle a request
     *
     * @param string $resource The resource accessed including the public key
     * @param string $method The HTTP method (one of the defined constants)
     * @throws PHPIMS\Exception
     */
    public function handle($resource, $method) {
        if (!$this->isValidMethod($method)) {
            throw new Exception($method . ' not implemented', 501);
        }

        // Trim away slashes
        $resource = trim($resource, '/');
        $matches  = array();

        // See if
        if (!preg_match('#^(?<publicKey>[a-f0-9]{32})/(?<resource>(images|(?<imageIdentifier>[a-f0-9]{32}\.[a-zA-Z]{3,4})(?:/(?<extra>meta))?))$#', $resource, $matches)) {
            throw new Exception('Unknown resource', 400);
        }

        $publicKey = $matches['publicKey'];

        // Make sure we have a valid public and private key pair
        $keyPairs = $this->config['auth'];

        if (!isset($keyPairs[$publicKey])) {
            throw new Exception('Unknown public key', 400);
        }

        $privateKey = $keyPairs[$publicKey];

        $imageIdentifier = isset($matches['imageIdentifier']) ? $matches['imageIdentifier'] : null;
        $extra = isset($matches['extra']) ? $matches['extra'] : null;

        // Create the operation
        $operation = $this->resolveOperation($matches['resource'], $method, $imageIdentifier, $extra);
        $operation->setPublicKey($publicKey)->setPrivateKey($privateKey);
        $operation->run();

        return $operation->getResponse();
    }
}
