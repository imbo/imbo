<?php
/**
 * Imbo
 *
 * Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
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
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo;

use Imbo\Http\Request\RequestInterface,
    Imbo\Http\Response\ResponseInterface,
    Imbo\Resource\ResourceInterface,
    Imbo\Exception\RuntimeException,
    Imbo\Exception,
    Imbo\Image\Image;

/**
 * Front controller
 *
 * @package Core
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class FrontController {
    /**
     * Dependency injection container
     *
     * @var Container
     */
    private $container;

    /**
     * HTTP methods supported one way or another in Imbo
     *
     * @var array
     */
    static private $supportedHttpMethods = array(
        RequestInterface::METHOD_GET    => true,
        RequestInterface::METHOD_POST   => true,
        RequestInterface::METHOD_PUT    => true,
        RequestInterface::METHOD_HEAD   => true,
        RequestInterface::METHOD_DELETE => true,
        RequestInterface::METHOD_BREW   => true,
    );

    /**
     * Default class names for the supported resources
     *
     * This is the fallback map if the resource is not located in the DIC.
     *
     * @var array
     */
    static private $resourceClasses = array(
        ResourceInterface::STATUS   => 'Imbo\Resource\Status',
        ResourceInterface::USER     => 'Imbo\Resource\User',
        ResourceInterface::IMAGES   => 'Imbo\Resource\Images',
        ResourceInterface::IMAGE    => 'Imbo\Resource\Image',
        ResourceInterface::METADATA => 'Imbo\Resource\Metadata',
    );

    /**
     * Class constructor
     *
     * @param Container $container A container instance
     */
    public function __construct(Container $container) {
        $this->container = $container;
    }

    /**
     * Create a resource object based on the request
     *
     * @param RequestInterface $request A request instance
     * @return ResourceInterface
     * @throws RuntimeException
     */
    private function resolveResource(RequestInterface $request) {
        // Fetch current path
        $path = $request->getPath();

        // Possible patterns to match where the most accessed match is placed first
        $routes = array(
            ResourceInterface::IMAGE    => '#^/users/(?<publicKey>[a-zA-Z0-9]{3,})/images/(?<imageIdentifier>[a-f0-9]{32})(/|.(?<extension>gif|jpg|png))?$#',
            ResourceInterface::STATUS   => '#^/status(/|(\.(?<extension>json|html|xml)))?$#',
            ResourceInterface::IMAGES   => '#^/users/(?<publicKey>[a-zA-Z0-9]{3,})/images(/|(\.(?<extension>json|html|xml)))?$#',
            ResourceInterface::METADATA => '#^/users/(?<publicKey>[a-zA-Z0-9]{3,})/images/(?<imageIdentifier>[a-f0-9]{32})/meta(/|\.(?<extension>json|html|xml))?$#',
            ResourceInterface::USER     => '#^/users/(?<publicKey>[a-zA-Z0-9]{3,})(/|\.(?<extension>json|html|xml))?$#',
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
            throw new RuntimeException('Not found', 404);
        }

        // Set the resource name
        $request->setResource($resourceName);

        // Extract some information from the path and store in the request instance
        if (!empty($matches['publicKey'])) {
            $request->setPublicKey($matches['publicKey']);
        }

        if (isset($matches['imageIdentifier'])) {
            $request->setImageIdentifier($matches['imageIdentifier']);
        }

        if (isset($matches['extension'])) {
            $request->setExtension($matches['extension']);
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

        // Attach the event manager to the resource
        $resource->setEventManager($this->container->eventManager);

        return $resource;
    }

    /**
     * Handle a request
     *
     * @param RequestInterface $request The request object
     * @param ResponseInterface $response The response object
     * @throws RuntimeException
     */
    public function handle(RequestInterface $request, ResponseInterface $response) {
        $httpMethod = $request->getMethod();

        if ($httpMethod === RequestInterface::METHOD_BREW) {
            throw new RuntimeException('I\'m a teapot!', 418);
        }

        if (!isset(self::$supportedHttpMethods[$httpMethod])) {
            throw new RuntimeException('Unsupported HTTP method: ' . $httpMethod, 501);
        }

        // Fetch a resource instance based on the request path
        $resource = $this->resolveResource($request);

        // Add some response headers
        $responseHeaders = $response->getHeaders();

        // Inform the user agent of which methods are allowed against this resource
        $responseHeaders->set('Allow', implode(', ', $resource->getAllowedMethods()));

        // Add Accept to Vary if the client has not specified a specific extension, in which we
        // won't do any content negotiation at all.
        if (!$request->getExtension()) {
            $responseHeaders->set('Vary', 'Accept');
        }

        // Fetch the real image identifier (PUT only) or the one from the URL (if present)
        if (($identifier = $request->getRealImageIdentifier()) || ($identifier = $request->getImageIdentifier())) {
            $response->getHeaders()->set('X-Imbo-ImageIdentifier', $identifier);
        }

        // Fetch auth config
        $authConfig = $this->container->config['auth'];
        $publicKey = $request->getPublicKey();

        // See if the public key exists
        if ($publicKey) {
            if (!isset($authConfig[$publicKey])) {
                $e = new RuntimeException('Unknown public key', 404);
                $e->setImboErrorCode(Exception::AUTH_UNKNOWN_PUBLIC_KEY);

                throw $e;
            }

            // Fetch the private key from the config and store it in the request
            $privateKey = $authConfig[$publicKey];
            $request->setPrivateKey($privateKey);
        }

        // Lowercase the HTTP method to get the class method to execute
        $methodName = strtolower($httpMethod);

        // See if the HTTP method is supported at all
        if (!method_exists($resource, $methodName)) {
            throw new RuntimeException('Method not allowed', 405);
        }

        $className = get_class($resource);
        $resourceName = strtolower(substr($className, strrpos($className, '\\') + 1));
        $eventName = $resourceName . '.' . $methodName;

        $this->container->eventManager->trigger($eventName . '.pre');
        $resource->$methodName($request, $response, $this->container->database, $this->container->storage);
        $this->container->eventManager->trigger($eventName . '.post');
    }
}
