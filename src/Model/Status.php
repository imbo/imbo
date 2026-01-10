<?php declare(strict_types=1);

namespace Imbo\Model;

use DateTime;

class Status implements ModelInterface
{
    /**
     * Date.
     */
    private ?DateTime $date = null;

    /**
     * Database status.
     */
    private ?bool $databaseStatus = null;

    /**
     * Storage status.
     */
    private ?bool $storageStatus = null;

    /**
     * Set the date.
     */
    public function setDate(DateTime $date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get the date.
     */
    public function getDate(): ?DateTime
    {
        return $this->date;
    }

    /**
     * Set the database status.
     */
    public function setDatabaseStatus(bool $status): self
    {
        $this->databaseStatus = $status;

        return $this;
    }

    /**
     * Get the database status.
     */
    public function getDatabaseStatus(): ?bool
    {
        return $this->databaseStatus;
    }

    /**
     * Set the storage status.
     */
    public function setStorageStatus(bool $status): self
    {
        $this->storageStatus = $status;

        return $this;
    }

    /**
     * Get the storage status.
     */
    public function getStorageStatus(): ?bool
    {
        return $this->storageStatus;
    }

    /**
     * @return array{date:DateTime,database:bool,storage:bool}
     */
    public function getData(): array
    {
        return [
            'date' => $this->getDate(),
            'database' => $this->getDatabaseStatus(),
            'storage' => $this->getStorageStatus(),
        ];
    }
}
