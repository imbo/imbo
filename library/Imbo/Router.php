<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo;

use Imbo\Resource\ResourceInterface,
    Imbo\EventManager\EventInterface,
    Imbo\EventListener\ListenerDefinition,
    Imbo\EventListener\ListenerInterface,
    Imbo\Exception\RuntimeException;

/**
 * Router class containing supported routes
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Router
 */
class Router implements ListenerInterface {
    /**
     * HTTP methods supported one way or another in Imbo
     *
     * @var array
     */
    static private $supportedHttpMethods = array(
        'GET'     => true,
        'POST'    => true,
        'PUT'     => true,
        'HEAD'    => true,
        'DELETE'  => true,
        'BREW'    => true,
        'OPTIONS' => true,
    );

    /**
     * The different routes that imbo handles
     *
     * @var array
     */
    public $routes = array(
        ResourceInterface::IMAGE    => '#^/users/(?<publicKey>[a-z0-9_-]{3,})/images/(?<imageIdentifier>[a-f0-9]{32})(.(?<extension>gif|jpg|png))?$#',
        ResourceInterface::STATUS   => '#^/status(/|(\.(?<extension>json|xml)))?$#',
        ResourceInterface::IMAGES   => '#^/users/(?<publicKey>[a-z0-9_-]{3,})/images(/|(\.(?<extension>json|xml)))?$#',
        ResourceInterface::METADATA => '#^/users/(?<publicKey>[a-z0-9_-]{3,})/images/(?<imageIdentifier>[a-f0-9]{32})/meta(/|\.(?<extension>json|xml))?$#',
        ResourceInterface::USER     => '#^/users/(?<publicKey>[a-z0-9_-]{3,})(/|\.(?<extension>json|xml))?$#',
    );

    /**
     * {@inheritdoc}
     */
    public function getDefinition() {
        return array(
            new ListenerDefinition('route', array($this, 'route')),
        );
    }

    /**
     * Resolve the current route
     *
     * @param EventInterface $event An event instance
     */
    public function route(EventInterface $event) {
        $request = $event->getRequest();
        $httpMethod = $request->getMethod();

        if ($httpMethod === 'BREW') {
            throw new RuntimeException('I\'m a teapot!', 418);
        }

        if (!isset(self::$supportedHttpMethods[$httpMethod])) {
            throw new RuntimeException('Unsupported HTTP method: ' . $httpMethod, 501);
        }

        $path = $request->getPathInfo();
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
