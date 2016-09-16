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
 * Group model
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Models
 */
class Group implements ModelInterface {
    /**
     * Name of the group
     *
     * @var string
     */
    private $name;

    /**
     * Resources
     *
     * @var string[]
     */
    private $resources = [];

    /**
     * Set the group name
     *
     * @param string $name The name of the group
     * @return self
     */
    public function setName($name) {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the group name
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set the group resources
     *
     * @param string[] $resources
     * @return self
     */
    public function setResources(array $resources = []) {
        $this->resources = $resources;

        return $this;
    }

    /**
     * Get the group resources
     *
     * @return string[]
     */
    public function getResources() {
        return $this->resources;
    }

    /**
     * {@inheritdoc}
     */
    public function getData() {
        return [
            'name' => $this->getName(),
            'resources' => $this->getResources(),
        ];
    }
}
