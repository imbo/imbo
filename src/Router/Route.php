<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Router;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * A class representing the current route matched by the router
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
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
