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

use Imbo\Exception\InvalidArgumentException,
    ArrayAccess;

/**
 * Statistics model
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Models
 */
class Stats implements ModelInterface, ArrayAccess {
    /**
     * Custom stats that can be set
     *
     * @var array
     */
    private $customStats = array();

    /**
     * User stats
     *
     * Keys are the users, and the values is an array with two elements:
     *
     * (int) 'numImages' Number of images stored by this user
     * (int) 'numBytes' Number of bytes stored by this user
     *
     * @var int
     */
    private $users = array();

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

    /**
     * Get custom stats
     *
     * @return array
     */
    public function getCustomStats() {
        return $this->customStats;
    }

    /**
     * @link http://www.php.net/manual/en/arrayaccess.offsetexists.php
     */
    public function offsetExists($offset) {
        return isset($this->customStats[$offset]);
    }

    /**
     * @link http://www.php.net/manual/en/arrayaccess.offsetget.php
     */
    public function offsetGet($offset) {
        return $this->customStats[$offset];
    }

    /**
     * @link http://www.php.net/manual/en/arrayaccess.offsetset.php
     */
    public function offsetSet($offset, $value) {
        if ($offset === null) {
            throw new InvalidArgumentException('Custom statistics requires a key to be set', 500);
        }

        $this->customStats[$offset] = $value;
    }

    /**
     * @link http://www.php.net/manual/en/arrayaccess.offsetunset.php
     */
    public function offsetUnset($offset) {
        unset($this->customStats[$offset]);
    }
}
