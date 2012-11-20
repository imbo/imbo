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

use Imbo\Resource\ResourceInterface,
    Imbo\Http\Request\RequestInterface,
    Imbo\EventManager\EventInterface,
    Imbo\EventManager\EventManagerInterface,
    Imbo\Exception\RuntimeException;

/**
 * Router class containing supported routes
 *
 * @package Core
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class Router implements ContainerAware, RouterInterface {
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
     * The different routes that imbo handles
     *
     * @var array
     */
    public $routes = array(
        ResourceInterface::IMAGE    => '#^/users/(?<publicKey>[a-z0-9_-]{3,})/images/(?<imageIdentifier>[a-f0-9]{32})(/|.(?<extension>gif|jpg|png))?$#',
        ResourceInterface::STATUS   => '#^/status(/|(\.(?<extension>json|html|xml)))?$#',
        ResourceInterface::IMAGES   => '#^/users/(?<publicKey>[a-z0-9_-]{3,})/images(/|(\.(?<extension>json|html|xml)))?$#',
        ResourceInterface::METADATA => '#^/users/(?<publicKey>[a-z0-9_-]{3,})/images/(?<imageIdentifier>[a-f0-9]{32})/meta(/|\.(?<extension>json|html|xml))?$#',
        ResourceInterface::USER     => '#^/users/(?<publicKey>[a-z0-9_-]{3,})(/|\.(?<extension>json|html|xml))?$#',
    );

    /**
     * @var Container
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(Container $container) {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function attach(EventManagerInterface $manager) {
        $manager->attach('route', array($this, 'route'));
    }

    /**
     * {@inheritdoc}
     */
    public function route(EventInterface $event) {
        $request = $event->getRequest();
        $httpMethod = $request->getMethod();

        if ($httpMethod === RequestInterface::METHOD_BREW) {
            throw new RuntimeException('I\'m a teapot!', 418);
        }

        if (!isset(self::$supportedHttpMethods[$httpMethod])) {
            throw new RuntimeException('Unsupported HTTP method: ' . $httpMethod, 501);
        }

        $path = $request->getPath();
        $matches = array();

        foreach ($this->routes as $resourceName => $route) {
            if (preg_match($route, $path, $matches)) {
                break;
            }
        }

        // Path matched no route
        if (!$matches) {
            throw new RuntimeException('Not Found', 404);
        }

        // Set the resource name
        $request->setResource($resourceName);

        if (!empty($matches['publicKey'])) {
            $request->setPublicKey($matches['publicKey']);
        }

        if (isset($matches['imageIdentifier'])) {
            $request->setImageIdentifier($matches['imageIdentifier']);
        }

        if (isset($matches['extension'])) {
            $request->setExtension($matches['extension']);
        }
    }
}
