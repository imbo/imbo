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
 * Status model
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Models
 */
class Status implements ModelInterface {
    /**
     * Date
     *
     * @var DateTime
     */
    private $date;

    /**
     * Database status
     *
     * @var boolean
     */
    private $databaseStatus;

    /**
     * Storage status
     *
     * @var boolean
     */
    private $storageStatus;

    /**
     * Set the date
     *
     * @param DateTime $date A DateTime instance
     * @return Status
     */
    public function setDate(DateTime $date) {
        $this->date = $date;

        return $this;
    }

    /**
     * Get the date
     *
     * @return DateTime
     */
    public function getDate() {
        return $this->date;
    }

    /**
     * Set the database status
     *
     * @param boolean $status The status flag
     * @return Status
     */
    public function setDatabaseStatus($status) {
        $this->databaseStatus = (boolean) $status;

        return $this;
    }

    /**
     * Get the database status
     *
     * @return boolean
     */
    public function getDatabaseStatus() {
        return $this->databaseStatus;
    }

    /**
     * Set the storage status
     *
     * @param boolean $status The status flag
     * @return Status
     */
    public function setStorageStatus($status) {
        $this->storageStatus = (boolean) $status;

        return $this;
    }

    /**
     * Get the storage status
     *
     * @return boolean
     */
    public function getStorageStatus() {
        return $this->storageStatus;
    }

    /**
     * {@inheritdoc}
     */
    public function getData() {
        return [
            'date' => $this->getDate(),
            'database' => $this->getDatabaseStatus(),
            'storage' => $this->getStorageStatus(),
        ];
    }
}
