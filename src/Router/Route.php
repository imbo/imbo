<?php
namespace Imbo\Router;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * A class representing the current route matched by the router
 *
 * @package Router
 */
class Route extends ParameterBag {
    /**
     * Route name
     *
     * @var string
     */
    private $name;

    /**
     * Set the route name
     *
     * @param string $name The name of the route
     * @return self
     */
    public function setName($name) {
        $this->name = $name;

        return $this;
    }

    /**
     * Return the route name
     *
     * @return string
     */
    public function __toString() {
        return (string) $this->name;
    }
}
