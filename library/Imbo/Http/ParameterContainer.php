<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Http;

/**
 * Parameter container
 *
 * Instances of this container will usually hold paramters found in for instance the $_GET or
 * $_POST superglobals.
 *
 * @package Http
 * @subpackage Containers
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class ParameterContainer {
    /**
     * Parameters in the container
     *
     * @var array
     */
    protected $parameters;

    /**
     * Class constructor
     *
     * @param array $parameters Parameters to store in the container
     */
    public function __construct(array $parameters) {
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function getAll() {
        return $this->parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function removeAll() {
        $this->parameters = array();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value) {
        $this->parameters[$key] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null) {
        return isset($this->parameters[$key]) ? $this->parameters[$key] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key) {
        unset($this->parameters[$key]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function has($key) {
        return isset($this->parameters[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function asString() {
        // Translate %5B and %5D back to [] as the client uses [] when generating the access token
        return preg_replace('/%5B\d+%5D/', '[]', http_build_query($this->parameters));
    }
}
