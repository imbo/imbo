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
 * Statistics model
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Models
 */
class Stats implements ModelInterface {
    /**
     * Total number of images stored
     *
     * @var int
     */
    private $numImages;

    /**
     * Total number of users
     *
     * @var int
     */
    private $numUsers;

    /**
     * Total number of bytes stored in the storage
     *
     * @var int
     */
    private $numBytes;

    /**
     * User stats
     *
     * Keys are the public keys of the users , and the values is an array with two elements:
     *
     * (int) 'numImages' Number of images stored by this user
     * (int) 'numBytes' Number of bytes stored by this user
     *
     * @var int
     */
    public $users = array();

    /**
     * Set the users information
     *
     * @param array $users The user info to set
     * @return Stats
     */
    public function setUsers(array $users) {
        $this->users = $users;

        return $this;
    }

    /**
     * Get the user information
     *
     * @return array
     */
    public function getUsers() {
        return $this->users;
    }

    /**
     * Get the total amount of bytes
     *
     * @return int
     */
    public function getNumBytes() {
        $sum = 0;

        foreach ($this->users as $user) {
            $sum += $user['numBytes'];
        }

        return $sum;
    }

    /**
     * Get the total amount of bytes
     *
     * @return int
     */
    public function getNumImages() {
        $sum = 0;

        foreach ($this->users as $user) {
            $sum += $user['numImages'];
        }

        return $sum;
    }

    /**
     * Get the amount of users
     *
     * @return int
     */
    public function getNumUsers() {
        return count($this->users);
    }
}
