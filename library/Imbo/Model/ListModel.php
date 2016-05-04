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
     * The entry name
     *
     * @var string
     */
    private $entry;

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
     * Get the entry value
     *
     * @return string
     */
    public function getEntry() {
        return $this->entry;
    }

    /**
     * Set the entry value
     *
     * @param string $entry
     * @return self
     */
    public function setEntry($entry) {
        $this->entry = $entry;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getData() {
        return [
            'list' => $this->getList(),
            'container' => $this->getContainer(),
            'entry' => $this->getEntry(),
        ];
    }
}
