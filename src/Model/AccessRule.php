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
 * Access rule model
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Models
 */
class AccessRule implements ModelInterface {
    /**
     * ID of the rule
     *
     * @var int
     */
    private $id;

    /**
     * Group name
     *
     * @var string
     */
    private $group;

    /**
     * List of resources
     *
     * @var string[]
     */
    private $resources = [];

    /**
     * List of users
     *
     * @var string[]
     */
    private $users = [];

    /**
     * Set the ID
     *
     * @param int $id
     * @return self
     */
    public function setId($id) {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the ID
     *
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set the group
     *
     * @param string $group
     * @return self
     */
    public function setGroup($group) {
        $this->group = $group;

        return $this;
    }

    /**
     * Get the group
     *
     * @return string
     */
    public function getGroup() {
        return $this->group;
    }

    /**
     * Set the resources
     *
     * @param string[] $resources
     * @return self
     */
    public function setResources(array $resources) {
        $this->resources = $resources;

        return $this;
    }

    /**
     * Get the resources
     *
     * @return string[]
     */
    public function getResources() {
        return $this->resources;
    }

    /**
     * Set the users
     *
     * @param string[] $users
     * @return self
     */
    public function setUsers(array $users) {
        $this->users = $users;

        return $this;
    }

    /**
     * Get the users
     *
     * @return string[]
     */
    public function getUsers() {
        return $this->users;
    }

    /**
     * {@inheritdoc}
     */
    public function getData() {
        return [
            'id' => $this->getId(),
            'group' => $this->getGroup(),
            'resources' => $this->getResources(),
            'users' => $this->getUsers(),
        ];
    }
}
