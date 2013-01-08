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
 * Header container
 *
 * This container contains HTTP headers along with some methods for normalizing the header names.
 *
 * @package Http
 * @subpackage Containers
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class HeaderContainer extends ParameterContainer {
    /**
     * Class constructor
     *
     * @param array $parameters Parameters to store in the container
     */
    public function __construct(array $parameters = array()) {
        foreach ($parameters as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Normalize the header name
     *
     * @param string $name The name to normalize, for instance "IF_MODIFIED_SINCE"
     * @return string The normalized name, for instance "if-modified-since"
     */
    private function getName($name) {
        return strtolower(str_replace('_', '-', $name));
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value) {
        return parent::set($this->getName($key), $value);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null) {
        return parent::get($this->getName($key), $default);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key) {
        return parent::remove($this->getName($key));
    }

    /**
     * {@inheritdoc}
     */
    public function has($key) {
        return parent::has($this->getName($key));
    }
}
