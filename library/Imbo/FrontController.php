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
        $matches = array();
        $resource = $this->container->router->resolve($request->getPath(), $matches);

        $this->container->eventManager->trigger('route.resolved', $matches);

        // Set the resource name
        if (!empty($matches['resourceName'])) {
            $request->setResource($matches['resourceName']);
        }

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

        // Attach the event manager to the resource
        $resource->setEventManager($this->container->eventManager);

        return $resource;
    }

    /**
     * Handle a request
     *
     * @throws RuntimeException
     */
    public function run() {
        $httpMethod = $this->container->request->getMethod();

        if ($httpMethod === RequestInterface::METHOD_BREW) {
            throw new RuntimeException('I\'m a teapot!', 418);
        }

        if (!isset(self::$supportedHttpMethods[$httpMethod])) {
            throw new RuntimeException('Unsupported HTTP method: ' . $httpMethod, 501);
        }

        // Fetch a resource instance based on the request path
        $resource = $this->resolveResource($this->container->request);

        // Add some response headers
        $responseHeaders = $this->container->response->getHeaders();

        // Inform the user agent of which methods are allowed against this resource
        $responseHeaders->set('Allow', implode(', ', $resource->getAllowedMethods()));

        // Add Accept to Vary if the client has not specified a specific extension, in which we
        // won't do any content negotiation at all.
        if (!$this->container->request->getExtension()) {
            $responseHeaders->set('Vary', 'Accept');
        }

        // Fetch the real image identifier (PUT only) or the one from the URL (if present)
        if (($identifier = $this->container->request->getRealImageIdentifier()) ||
            ($identifier = $this->container->request->getImageIdentifier())) {
            $responseHeaders->set('X-Imbo-ImageIdentifier', $identifier);
        }

        // Fetch auth config
        $authConfig = $this->container->config['auth'];
        $publicKey = $this->container->request->getPublicKey();

        // See if the public key exists
        if ($publicKey) {
            if (!isset($authConfig[$publicKey])) {
                $e = new RuntimeException('Unknown Public Key', 404);
                $e->setImboErrorCode(Exception::AUTH_UNKNOWN_PUBLIC_KEY);

                throw $e;
            }

            // Fetch the private key from the config and store it in the request
            $privateKey = $authConfig[$publicKey];
            $this->container->request->setPrivateKey($privateKey);
        }

        // Lowercase the HTTP method to get the class method to execute
        $methodName = strtolower($httpMethod);

        // Generate the event name based on the accessed resource and the HTTP method
        $eventName = $this->container->request->getResource() . '.' . $methodName;

        // Trigger the pre event on the resource so we may react to unsupported events
        $this->container->eventManager->trigger($eventName . '.pre');

        // See if the HTTP method is supported at all
        if (!method_exists($resource, $methodName)) {
            throw new RuntimeException('Method not allowed', 405);
        }

        $resource->$methodName($this->container);
        $this->container->eventManager->trigger($eventName . '.post');
    }
}
