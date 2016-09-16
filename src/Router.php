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

use Imbo\Http\Request\Request,
    Imbo\Exception\RuntimeException,
    Imbo\Router\Route;

/**
 * Router class containing supported routes
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Router
 */
class Router {
    /**
     * HTTP methods supported one way or another in Imbo
     *
     * @var array
     */
    static private $supportedHttpMethods = [
        'GET'     => true,
        'POST'    => true,
        'PUT'     => true,
        'HEAD'    => true,
        'DELETE'  => true,
        'OPTIONS' => true,
        'SEARCH'  => true,
    ];

    /**
     * The different routes that imbo handles
     *
     * @var array
     */
    private $routes = [
        'image'          => '#^/users/(?<user>[a-z0-9_-]{1,})/images/(?<imageIdentifier>[A-Za-z0-9_-]{1,255})(\.(?<extension>gif|jpg|png))?$#',
        'globalshorturl' => '#^/s/(?<shortUrlId>[a-zA-Z0-9]{7})$#',
        'status'         => '#^/status(/|(\.(?<extension>json)))?$#',
        'images'         => '#^/users/(?<user>[a-z0-9_-]{1,})/images(/|(\.(?<extension>json)))?$#',
        'globalimages'   => '#^/images(/|(\.(?<extension>json)))?$#',
        'metadata'       => '#^/users/(?<user>[a-z0-9_-]{1,})/images/(?<imageIdentifier>[A-Za-z0-9_-]{1,255})/meta(?:data)?(/|\.(?<extension>json))?$#',
        'user'           => '#^/users/(?<user>[a-z0-9_-]{1,})(/|\.(?<extension>json))?$#',
        'stats'          => '#^/stats(/|(\.(?<extension>json)))?$#',
        'index'          => '#^/?$#',
        'shorturls'      => '#^/users/(?<user>[a-z0-9_-]{1,})/images/(?<imageIdentifier>[A-Za-z0-9_-]{1,255})/shorturls(/|\.(?<extension>json))?$#',
        'shorturl'       => '#^/users/(?<user>[a-z0-9_-]{1,})/images/(?<imageIdentifier>[A-Za-z0-9_-]{1,255})/shorturls/(?<shortUrlId>[a-zA-Z0-9]{7})$#',
        'groups'         => '#^/groups(/|(\.(?<extension>json)))?$#',
        'group'          => '#^/groups/(?<group>[a-z0-9_-]{1,})(/|\.(?<extension>json))?$#',
        'keys'           => '#^/keys/(?<publickey>[a-z0-9_-]{1,})$#',
        'accessrules'    => '#^/keys/(?<publickey>[a-z0-9_-]{1,})/access(/|(\.(?<extension>json)))?$#',
        'accessrule'     => '#^/keys/(?<publickey>[a-z0-9_-]{1,})/access/(?<accessRuleId>[a-f0-9]{1,})(\.(?<extension>json))?$#',
    ];

    /**
     * Class constructor
     *
     * @param array $extraRoutes Extra routes passed in from configuration
     */
    public function __construct(array $extraRoutes = []) {
        $this->routes = array_merge($this->routes, $extraRoutes);
    }

    /**
     * Route the current request
     *
     * @param Request $request The current request
     */
    public function route(Request $request) {
        $httpMethod = $request->getMethod();

        if ($httpMethod === 'BREW') {
            throw new RuntimeException('I\'m a teapot!', 418);
        }

        if (!isset(self::$supportedHttpMethods[$httpMethod])) {
            throw new RuntimeException('Unsupported HTTP method: ' . $httpMethod, 501);
        }

        $path = $request->getPathInfo();
        $matches = [];

        foreach ($this->routes as $resourceName => $route) {
            if (preg_match($route, $path, $matches)) {
                break;
            }
        }

        // Path matched no route
        if (!$matches) {
            throw new RuntimeException('Not Found', 404);
        }

        // Create and populate a route instance that we want to inject into the request
        $route = new Route();
        $route->setName($resourceName);

        // Inject all matches into the route as parameters
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $route->set($key, $value);
            }
        }

        // Store the route in the request
        $request->setRoute($route);
    }
}
