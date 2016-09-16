<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Model;

/**
 * Simple model using an numerically indexed array for data
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Models
 */
class ListModel implements ModelInterface {
    /**
     * The list
     *
     * @var array
     */
    private $list = [];

    /**
     * The container name
     *
     * @var string
     */
    private $container;

    /**
     * Class constructor
     *
     * @param string $container
     * @param array $list
     */
    public function __construct($container = null, array $list = []) {
        $this->container = $container;
        $this->list = $list;
    }

    /**
     * Get the list
     *
     * @return array
     */
    public function getList() {
        return $this->list;
    }

    /**
     * Set the list
     *
     * @param array $list The list itself
     * @return self
     */
    public function setList(array $list) {
        $this->list = $list;

        return $this;
    }

    /**
     * Get the container value
     *
     * @return string
     */
    public function getContainer() {
        return $this->container;
    }

    /**
     * Set the container value
     *
     * @param string $container
     * @return self
     */
    public function setContainer($container) {
        $this->container = $container;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getData() {
        return [
            'container' => $this->getContainer(),
            'list' => $this->getList(),
        ];
    }
}
