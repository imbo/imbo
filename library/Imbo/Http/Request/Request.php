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
     * The private key
     *
     * @var string
     */
    private $privateKey;

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
     * Get an image model attached to the request (on PUT)
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
        return $this->route ? $this->route->get('publicKey') : null;
    }

    /**
     * Get the private key
     *
     * The private key property is populated by the server based on the public key from the
     * request. The client itself does not place the private key in the request.
     *
     * @return string
     */
    public function getPrivateKey() {
        return $this->privateKey;
    }

    /**
     * Set the private key
     *
     * @param string $key The key to set
     * @return Request
     */
    public function setPrivateKey($key) {
        $this->privateKey = $key;

        return $this;
    }

    /**
     * Get image transformations from the request
     *
     * @return array
     */
    public function getTransformations() {
        if ($this->transformations === null) {
            $this->transformations = array();

            $transformations = $this->query->get('t', array());

            foreach ($transformations as $transformation) {
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
                $params = array();

                // See if we have more than one parameter
                if (strpos($urlParams, ',') !== false) {
                    $urlParams = explode(',', $urlParams);
                } else {
                    $urlParams = array($urlParams);
                }

                foreach ($urlParams as $param) {
                    $pos = strpos($param, '=');

                    if ($pos !== false) {
                        $params[substr($param, 0, $pos)] = substr($param, $pos + 1);
                    }
                }

                $this->transformations[] = array(
                    'name'   => $name,
                    'params' => $params,
                );
            }
        }

        return $this->transformations;
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
     * Get the URI without the Symfony normalization applied to the query string
     *
     * @return string
     */
    public function getRawUri() {
        $query = $this->server->get('QUERY_STRING');

        if (!empty($query)) {
            $query = '?' . $query;
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
