<?php
namespace Imbo\Model;

use DateTime;

class User implements ModelInterface {
    /**
     * User ID
     *
     * @var string
     */
    private $user;

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
     * Set the user ID
     *
     * @param string $user The user ID
     * @return User
     */
    public function setUserId($user) {
        $this->user = $user;

        return $this;
    }

    /**
     * Get the user ID
     *
     * @return string
     */
    public function getUserId() {
        return $this->user;
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

    /**
     * {@inheritdoc}
     */
    public function getData() {
        return [
            'id' => $this->getUserId(),
            'numImages' => $this->getNumImages(),
            'lastModified' => $this->getLastModified(),
        ];
    }
}
