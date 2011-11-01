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
 * @subpackage Server
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */

namespace Imbo;

use Imbo\Http\Request\RequestInterface;
use Imbo\Http\Response\ResponseInterface;
use Imbo\Image\Image;

/**
 * Client that interacts with the server part of Imbo
 *
 * This client includes methods that can be used to easily interact with a Imbo server
 *
 * @package Imbo
 * @subpackage Server
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */
class FrontController {
    /**
     * Dependency injection container
     *
     * @var Imbo\Container
     */
    private $container;

    /**
     * HTTP methods supported one way or another in Imbo
     *
     * @var array
     */
    static private $supportedHttpMethods = array(
        RequestInterface::METHOD_GET     => true,
        RequestInterface::METHOD_POST    => true,
        RequestInterface::METHOD_PUT     => true,
        RequestInterface::METHOD_HEAD    => true,
        RequestInterface::METHOD_DELETE  => true,
        RequestInterface::METHOD_BREW    => true,
        RequestInterface::METHOD_OPTIONS => true,
    );

    /**
     * Class constructor
     *
     * @param Imbo\Container $container A container instance
     */
    public function __construct(Container $container) {
        $this->container = $container;
    }

    /**
     * Create a resource object based on the request
     *
     * @param Imbo\Http\Request\RequestInterface $request A request instance
     * @return Imbo\Resource\ResourceInterface
     * @throws Imbo\Exception
     */
    private function resolveResource(RequestInterface $request) {
        // Fetch current path
        $path = $request->getPath();

        // Possible patterns to match where the most accessed patch is placed first
        $routes = array(
            'image'    => '#^/users/(?<publicKey>[a-f0-9]{32})/images/(?<resource>(?<imageIdentifier>[a-f0-9]{32})(/|.(gif|jpg|png))?)$#',
            'metadata' => '#^/users/(?<publicKey>[a-f0-9]{32})/images/(?<resource>(?<imageIdentifier>[a-f0-9]{32})(/|.(gif|jpg|png)/)meta/?)$#',
            'images'   => '#^/users/(?<publicKey>[a-f0-9]{32})/(?<resource>images)/?$#',
        );

        // Initialize matches
        $matches = array();

        foreach ($routes as $resourceName => $route) {
            if (preg_match($route, $path, $matches)) {
                break;
            }
        }

        // Path matched no route
        if (!$matches) {
            throw new Exception('Invalid request', 400);
        }

        // Extract some information from the path and store in the request instance
        $request->setPublicKey($matches['publicKey']);
        $request->setResource(rtrim($matches['resource'], '/'));

        if (isset($matches['imageIdentifier'])) {
            $request->setImageIdentifier($matches['imageIdentifier']);
        }

        // Append "Resource" to the resource name to match the entry in the container
        $resourceName .= 'Resource';

        // Fetch the resource instance from the container
        $resource = $this->container->$resourceName;

        return $resource;
    }

    /**
     * Authenticate the current request
     *
     * @param Imbo\Http\Request\RequestInterface $request The current request
     * @throws Imbo\Exception
     */
    private function auth(RequestInterface $request) {
        $authConfig = $this->container->auth;
        $publicKey = $request->getPublicKey();

        // See if the public key exists
        if (!isset($authConfig[$publicKey])) {
            throw new Exception('Unknown public key', 400);
        }

        $privateKey = $authConfig[$publicKey];

        $query = $request->getQuery();

        foreach (array('signature', 'timestamp') as $param) {
            if (!$query->has($param)) {
                throw new Exception('Missing required authentication parameter: ' . $param, 400);
            }
        }

        $timestamp = $query->get('timestamp');

        // Make sure the timestamp is in the correct format
        if (!preg_match('/^[\d]{4}-[\d]{2}-[\d]{2}T[\d]{2}:[\d]{2}:[\d]{2}(?:\.\d+)?Z$/', $timestamp)) {
            throw new Exception('Invalid authentication timestamp format: ' . $timestamp, 400);
        }

        $year   = substr($timestamp, 0, 4);
        $month  = substr($timestamp, 5, 2);
        $day    = substr($timestamp, 8, 2);
        $hour   = substr($timestamp, 11, 2);
        $minute = substr($timestamp, 14, 2);
        $second = substr($timestamp, 17, 2);

        $timestamp = gmmktime($hour, $minute, $second, $month, $day, $year);

        $diff = time() - $timestamp;

        if ($diff > 120 || $diff < -120) {
            throw new Exception('Authentication timestamp has expired', 403);
        }

        // Generate data for the HMAC
        $data = $request->getMethod() . '|' . $request->getResource() . '|' . $publicKey . '|' . $query->get('timestamp');

        // Generate binary hash key
        $actualSignature = hash_hmac('sha256', $data, $privateKey);

        if ($actualSignature !== $query->get('signature')) {
            throw new Exception('Signature mismatch', 403);
        }
    }

    /**
     * Handle a request
     *
     * @param Imbo\Http\Request\RequestInterface $request The request object
     * @param Imbo\Http\Response\ResponseInterface $response The response object
     * @throws Imbo\Exception
     */
    public function handle(RequestInterface $request, ResponseInterface $response) {
        $httpMethod = $request->getMethod();

        if ($httpMethod === RequestInterface::METHOD_BREW) {
            throw new Exception('I\'m a teapot!', 418);
        }

        if (!isset(self::$supportedHttpMethods[$httpMethod])) {
            throw new Exception('Unsupported HTTP method: ' . $httpMethod, 501);
        }

        // Fetch a resource instance based on the request path
        $resource = $this->resolveResource($request);

        // Add an Allow header to the response that contains the methods the resource has
        // implemented
        $response->getHeaders()->set('Allow', implode(', ', $resource->getAllowedMethods()));

        if ($identifier = $request->getImageIdentifier()) {
            $response->getHeaders()->set('X-Imbo-ImageIdentifier', $identifier);
        }

        // If we have an unsafe request, we need to make sure that the request is valid
        if ($request->isUnsafe()) {
            $this->auth($request);
        }

        // Lowercase the HTTP method to get the class method to execute
        $methodName = strtolower($httpMethod);

        // See if the HTTP method is supported at all
        if (!method_exists($resource, $methodName)) {
            throw new Exception('Method not allowed', 405);
        }

        $resource->$methodName($request, $response, $this->container->database, $this->container->storage);
    }
}
