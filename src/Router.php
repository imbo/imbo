<?php declare(strict_types=1);
namespace Imbo;

use Imbo\Exception\RuntimeException;
use Imbo\Http\Request\Request;
use Imbo\Router\Route;

/**
 * Router class containing supported routes
 */
class Router
{
    /**
     * HTTP methods supported one way or another in Imbo
     *
     * @var array<string,bool>
     */
    private static array $supportedHttpMethods = [
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
     * @var array<string,string>
     */
    private array $routes = [
        'image'          => '#^/users/(?<user>[a-z0-9_-]{1,})/images/(?<imageIdentifier>[A-Za-z0-9_-]{1,255})(\.(?<extension>[^/]*))?$#',
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
        'keys'           => '#^/keys(/|(\.(?<extension>json)))?$#',
        'key'            => '#^/keys/(?<publickey>[a-z0-9_-]{1,})$#',
        'accessrules'    => '#^/keys/(?<publickey>[a-z0-9_-]{1,})/access(/|(\.(?<extension>json)))?$#',
        'accessrule'     => '#^/keys/(?<publickey>[a-z0-9_-]{1,})/access/(?<accessRuleId>[a-f0-9]{1,})(\.(?<extension>json))?$#',
    ];

    /**
     * Class constructor
     *
     * @param array<string,string> $extraRoutes Extra routes passed in from configuration
     */
    public function __construct(array $extraRoutes = [])
    {
        $this->routes = array_merge($this->routes, $extraRoutes);
    }

    /**
     * Route the current request
     *
     * @param Request $request The current request
     * @throws RuntimeException
     * @return void
     */
    public function route(Request $request): void
    {
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

        if ([] === $matches) {
            throw new RuntimeException('Not Found', 404);
        }

        $route = new Route();
        $route->setName($resourceName);

        // Inject all matches into the route as parameters
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $route->set($key, $value);
            }
        }

        $request->setRoute($route);
    }
}
