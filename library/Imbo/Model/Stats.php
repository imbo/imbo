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
    private $customStats = [];

    /**
     * Number of users in the system
     *
     * @var integer
     */
    private $numUsers;

    /**
     * Number of bytes in the system
     *
     * @var integer
     */
    private $numBytes;

    /**
     * Number of images in the system
     *
     * @var integer
     */
    private $numImages;

    /**
     * Set the number of users
     *
     * @param int $numUsers The number of users that has one or more images
     * @return Stats
     */
    public function setNumUsers($numUsers) {
        $this->numUsers = $numUsers;

        return $this;
    }

    /**
     * Get the number of users
     *
     * @return integer
     */
    public function getNumUsers() {
        return $this->numUsers;
    }

    /**
     * Set the number of bytes stored in Imbo
     *
     * @param int $numBytes The number of bytes stored in Imbo
     * @return Stats
     */
    public function setNumBytes($numBytes) {
        $this->numBytes = $numBytes;

        return $this;
    }

    /**
     * Get the total amount of bytes
     *
     * @return int
     */
    public function getNumBytes() {
        return $this->numBytes;
    }

    /**
     * Set the number of images stored in Imbo
     *
     * @param int $numImages The number of images stored in Imbo
     * @return Stats
     */
    public function setNumImages($numImages) {
        $this->numImages = $numImages;

        return $this;
    }

    /**
     * Get the total amount of bytes
     *
     * @return int
     */
    public function getNumImages() {
        return $this->numImages;
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

    /**
     * {@inheritdoc}
     */
    public function getData() {
        return [
            'numUsers' => $this->getNumUsers(),
            'numBytes' => $this->getNumBytes(),
            'numImages' => $this->getNumImages(),
            'customStats' => $this->getCustomStats(),
        ];
    }
}
