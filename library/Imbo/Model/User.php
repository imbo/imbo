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

use DateTime;

/**
 * User model
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Models
 */
class User implements ModelInterface {
    /**
     * Public key
     *
     * @var string
     */
    private $publicKey;

    /**
     * Number of images
     *
     * @var int
     */
    private $numImages;

    /**
     * Last modified
     *
     * @var DateTime
     */
    private $lastModified;

    /**
     * Set the public key
     *
     * @param string $publicKey The public key
     * @return User
     */
    public function setPublicKey($publicKey) {
        $this->publicKey = $publicKey;

        return $this;
    }

    /**
     * Get the public key
     *
     * @return string
     */
    public function getPublicKey() {
        return $this->publicKey;
    }

    /**
     * Set the number of images
     *
     * @param int $num The number to set
     * @return User
     */
    public function setNumImages($num) {
        $this->numImages = (int) $num;

        return $this;
    }

    /**
     * Get the number of images
     *
     * @return int
     */
    public function getNumImages() {
        return $this->numImages;
    }

    /**
     * Set the last modified date
     *
     * @param DateTime $date The DateTime instance
     * @return User
     */
    public function setLastModified(DateTime $date) {
        $this->lastModified = $date;

        return $this;
    }

    /**
     * Get the last modified date
     *
     * @return DateTime
     */
    public function getLastModified() {
        return $this->lastModified;
    }
}
