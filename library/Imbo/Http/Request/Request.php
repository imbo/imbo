<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Http\Request;

use Imbo\Exception\InvalidArgumentException,
    Imbo\Model\Image,
    Imbo\Router\Route,
    Symfony\Component\HttpFoundation\Request as SymfonyRequest;

/**
 * Request class
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Http
 */
class Request extends SymfonyRequest {
    /**
     * Image instance
     *
     * @var Image
     */
    private $image;

    /**
     * Array of transformations
     *
     * @var array
     */
    private $transformations;

    /**
     * The current route
     *
     * @param Route
     */
    private $route;

    /**
     * Set an image model
     *
     * @param Image $image An image model instance
     * @return Request
     */
    public function setImage(Image $image) {
        $this->image = $image;

        return $this;
    }

    /**
     * Get an image model attached to the request (on POST)
     *
     * @return null|Image
     */
    public function getImage() {
        return $this->image;
    }

    /**
     * Get the public key found in the request
     *
     * @return string
     */
    public function getPublicKey() {
        return (
            $this->headers->get('X-Imbo-PublicKey', null) ?:
            $this->query->get('publicKey', null) ?:
            ($this->route ? $this->route->get('user') : null)
        );
    }

    /**
     * Get the user found in the request
     *
     * @return string
     */
    public function getUser() {
        return $this->route ? $this->route->get('user') : null;
    }

    /**
     * Get users specified in the request
     *
     * @return array Users specified in the request
     */
    public function getUsers() {
        $routeUser = $this->getUser();
        $queryUsers = $this->query->get('users', []);

        if (!$routeUser && !$queryUsers) {
            return [];
        } elseif (!$queryUsers) {
            return [$routeUser];
        } elseif (!$routeUser) {
            return $queryUsers;
        }

        return array_merge([$routeUser], $queryUsers);
    }

    /**
     * Get image transformations from the request
     *
     * @return array
     */
    public function getTransformations() {
        if ($this->transformations === null) {
            $this->transformations = [];

            $transformations = $this->query->get('t', []);

            if (!is_array($transformations)) {
                throw new InvalidArgumentException('Transformations must be specifed as an array', 400);
            }

            foreach ($transformations as $transformation) {
                if (!is_string($transformation)) {
                    throw new InvalidArgumentException('Invalid transformation', 400);
                }

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
                $params = [];

                // Loop through the parameter string and assign params to an array
                $offset = 0;
                $pattern = '#(\w+)=(?:(.+?),\w+=|(.+?$))#';
                while (preg_match($pattern, $urlParams, $matches, PREG_OFFSET_CAPTURE, $offset)) {
                    $offset = $matches[2][1];
                    $paramName = $matches[1][0];
                    $paramValue = isset($matches[3]) ? $matches[3][0] : $matches[2][0];
                    $params[$paramName] = $paramValue;
                }

                $this->transformations[] = [
                    'name'   => $name,
                    'params' => $params,
                ];
            }
        }

        return $this->transformations;
    }

    /**
     * Set the transformation chain
     *
     * @param array $transformations The image transformations
     * @return self
     */
    public function setTransformations(array $transformations) {
        $this->transformations = $transformations;

        return $this;
    }

    /**
     * Get the image identifier from the URL
     *
     * @return string|null
     */
    public function getImageIdentifier() {
        return $this->route ? $this->route->get('imageIdentifier') : null;
    }

    /**
     * Get the current requested extension (if any)
     *
     * @return string|null
     */
    public function getExtension() {
        return $this->route ? $this->route->get('extension') : null;
    }

    /**
     * Get the URI with no changes to the incoming formatting ("as is")
     *
     * @return string
     */
    public function getUriAsIs() {
        $query = $this->server->get('QUERY_STRING');

        if (!empty($query)) {
            $query = '?' . $query;
        }

        return $this->getSchemeAndHttpHost() . $this->getBaseUrl() . $this->getPathInfo() . $query;
    }

    /**
     * Get the URI without the Symfony normalization applied to the query string, un-encoded
     *
     * @return string
     */
    public function getRawUri() {
        $query = $this->server->get('QUERY_STRING');

        if (!empty($query)) {
            $query = '?' . urldecode($query);
        }

        return $this->getSchemeAndHttpHost() . $this->getBaseUrl() . $this->getPathInfo() . $query;
    }

    /**
     * Get the current route
     *
     * @return Route
     */
    public function getRoute() {
        return $this->route;
    }

    /**
     * Set the route
     *
     * @param Route $route The current route
     * @return self
     */
    public function setRoute(Route $route) {
        $this->route = $route;

        return $this;
    }
}
