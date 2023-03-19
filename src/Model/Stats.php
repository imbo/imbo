<?php declare(strict_types=1);
namespace Imbo\Model;

use ArrayAccess;
use Imbo\Exception\InvalidArgumentException;
use Imbo\Http\Response\Response;

/**
 * @template-implements ArrayAccess<array-key, mixed>
 */
class Stats implements ModelInterface, ArrayAccess
{
    private array $customStats = [];
    private int $numUsers;
    private int $numBytes;
    private int $numImages;

    public function setNumUsers(int $numUsers): self
    {
        $this->numUsers = $numUsers;
        return $this;
    }

    public function getNumUsers(): int
    {
        return $this->numUsers;
    }

    public function setNumBytes(int $numBytes): self
    {
        $this->numBytes = $numBytes;
        return $this;
    }

    public function getNumBytes(): int
    {
        return $this->numBytes;
    }

    public function setNumImages(int $numImages): self
    {
        $this->numImages = $numImages;
        return $this;
    }

    public function getNumImages(): int
    {
        return $this->numImages;
    }

    public function getCustomStats(): array
    {
        return $this->customStats;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->customStats[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->customStats[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            throw new InvalidArgumentException('Custom statistics requires a key to be set', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->customStats[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->customStats[$offset]);
    }

    public function getData(): array
    {
        return [
            'numUsers' => $this->getNumUsers(),
            'numBytes' => $this->getNumBytes(),
            'numImages' => $this->getNumImages(),
            'customStats' => $this->getCustomStats(),
        ];
    }
}
