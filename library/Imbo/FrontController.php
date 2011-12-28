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
 * @package Core
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */

namespace Imbo;

use Imbo\Http\Request\RequestInterface,
    Imbo\Http\Response\ResponseInterface,
    Imbo\Image\Image,
    Imbo\Validate;

/**
 * Front controller
 *
 * @package Core
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
     * Timestamp validator
     *
     * @var Imbo\Validate\ValidateInterface
     */
    private $timestampValidator;

    /**
     * Signature validator
     *
     * @var Imbo\Validate\SignatureInterface
     */
    private $signatureValidator;

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
    );

    /**
     * Default class names for the supported resources
     *
     * This is the fallback map if the resource is not located in the DIC.
     *
     * @var array
     */
    static private $resourceClasses = array(
        'image'    => 'Imbo\Resource\Image',
        'metadata' => 'Imbo\Resource\Metadata',
        'images'   => 'Imbo\Resource\Images',
        'user'     => 'Imbo\Resource\User',
    );

    /**
     * Class constructor
     *
     * @param Imbo\Container $container A container instance
     * @param Imbo\Validate\ValidateInterface $timestampValidator A timestamp validator
     * @param Imbo\Validate\SignatureInterface $signatureValidator A signature validator
     */
    public function __construct(Container $container,
                                Validate\ValidateInterface $timestampValidator = null,
                                Validate\SignatureInterface $signatureValidator = null) {
        $this->container = $container;

        if ($timestampValidator === null) {
            $timestampValidator = new Validate\Timestamp();
        }

        if ($signatureValidator === null) {
            $signatureValidator = new Validate\Signature();
        }

        $this->timestampValidator = $timestampValidator;
        $this->signatureValidator = $signatureValidator;
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

        // Possible patterns to match where the most accessed match is placed first
        $routes = array(
            'image'    => '#^/users/(?<publicKey>[a-zA-Z0-9]{3,})/images/(?<imageIdentifier>[a-f0-9]{32})(/|.(gif|jpg|png))?$#',
            'images'   => '#^/users/(?<publicKey>[a-zA-Z0-9]{3,})/images/?$#',
            'metadata' => '#^/users/(?<publicKey>[a-zA-Z0-9]{3,})/images/(?<imageIdentifier>[a-f0-9]{32})(/|.(gif|jpg|png)/)meta/?$#',
            'user'     => '#^/users/(?<publicKey>[a-zA-Z0-9]{3,})/?$#',
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

        if (isset($matches['imageIdentifier'])) {
            $request->setImageIdentifier($matches['imageIdentifier']);
        }

        // Append "Resource" to the resource name to match the entry in the container
        $dicEntry = $resourceName . 'Resource';

        if ($this->container->has($dicEntry)) {
            // Fetch the resource instance from the container
            $resource = $this->container->$dicEntry;
        } else {
            $className = self::$resourceClasses[$resourceName];
            $resource = new $className();
        }

        return $resource;
    }

    /**
     * Authenticate the current request
     *
     * @param Imbo\Http\Request\RequestInterface $request The current request
     * @param Imbo\Http\Response\ResponseInterface $response The response instance
     * @throws Imbo\Exception
     * @return mixed Returns true on success
     */
    private function auth(RequestInterface $request, ResponseInterface $response) {
        $authConfig = $this->container->auth;
        $publicKey = $request->getPublicKey();

        // See if the public key exists
        if (!isset($authConfig[$publicKey])) {
            throw new Exception('Unknown public key', 400);
        }

        if (!$request->isUnsafe()) {
            return;
        }

        $privateKey = $authConfig[$publicKey];

        $query = $request->getQuery();

        foreach (array('signature', 'timestamp') as $param) {
            if (!$query->has($param)) {
                throw new Exception('Missing required authentication parameter: ' . $param, 400);
            }
        }

        $timestamp = $query->get('timestamp');

        if (!$this->timestampValidator->isValid($timestamp)) {
            throw new Exception('Invalid timestamp: ' . $timestamp, 400);
        }

        // Add the URL used for auth to the response headers
        $response->getHeaders()->set('X-Imbo-AuthUrl', $request->getUrl());

        $signature = $query->get('signature');
        $this->signatureValidator->setHttpMethod($request->getMethod())
                                 ->setUrl($request->getUrl())
                                 ->setTimestamp($timestamp)
                                 ->setPublicKey($publicKey)
                                 ->setPrivateKey($privateKey);

        if (!$this->signatureValidator->isValid($signature)) {
            throw new Exception('Signature mismatch', 403);
        }

        return true;
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

        // Add Allow and Vary to all responses
        $response->getHeaders()->set('Allow', implode(', ', $resource->getAllowedMethods()))
                               ->set('Vary', 'Accept, Accept-Encoding');

        if ($identifier = $request->getImageIdentifier()) {
            $response->getHeaders()->set('X-Imbo-ImageIdentifier', $identifier);
        }

        // Authenticate the request
        $this->auth($request, $response);

        // Lowercase the HTTP method to get the class method to execute
        $methodName = strtolower($httpMethod);

        // See if the HTTP method is supported at all
        if (!method_exists($resource, $methodName)) {
            throw new Exception('Method not allowed', 405);
        }

        $className = get_class($resource);
        $resourceName = strtolower(substr($className, strrpos($className, '\\') + 1));
        $eventName = $resourceName . '.' . $methodName;

        $this->container->eventManager->trigger($eventName . '.pre');
        $resource->$methodName($request, $response, $this->container->database, $this->container->storage);
        $this->container->eventManager->trigger($eventName . '.post');
    }
}
