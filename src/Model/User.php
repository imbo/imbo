<?php declare(strict_types=1);

namespace Imbo\Model;

use DateTime;

class User implements ModelInterface
{
    /**
     * User ID.
     */
    private ?string $user = null;

    /**
     * Number of images.
     */
    private ?int $numImages = null;

    /**
     * Last modified.
     */
    private ?DateTime $lastModified = null;

    /**
     * Set the user ID.
     */
    public function setUserId(string $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get the user ID.
     */
    public function getUserId(): ?string
    {
        return $this->user;
    }

    /**
     * Set the number of images.
     */
    public function setNumImages(int $num): self
    {
        $this->numImages = $num;

        return $this;
    }

    /**
     * Get the number of images.
     */
    public function getNumImages(): ?int
    {
        return $this->numImages;
    }

    /**
     * Set the last modified date.
     */
    public function setLastModified(DateTime $date): self
    {
        $this->lastModified = $date;

        return $this;
    }

    /**
     * Get the last modified date.
     */
    public function getLastModified(): ?DateTime
    {
        return $this->lastModified;
    }

    /**
     * @return array{id:string,numImages:int,lastModified:DateTime}
     */
    public function getData(): array
    {
        return [
            'id' => $this->getUserId(),
            'numImages' => $this->getNumImages(),
            'lastModified' => $this->getLastModified(),
        ];
    }
}
