<?php declare(strict_types=1);
namespace Imbo\Storage;

use DateTime;
use Imbo\Exception\StorageException;

class Filesystem implements StorageInterface
{
    private string $dataDir;

    /**
     * Class constructor
     *
     * @param string $dataDir Directory to store the files in
     */
    public function __construct(string $dataDir)
    {
        $this->dataDir = $dataDir;
    }

    public function store(string $user, string $imageIdentifier, string $imageData): bool
    {
        if (!is_writable($this->dataDir)) {
            throw new StorageException('Could not store image', 500);
        }

        if ($this->imageExists($user, $imageIdentifier)) {
            return touch($this->getImagePath($user, $imageIdentifier));
        }

        $imageDir = dirname($this->getImagePath($user, $imageIdentifier));
        $oldUmask = umask(0);

        if (!is_dir($imageDir)) {
            mkdir($imageDir, 0775, true);
        }

        umask($oldUmask);

        $imagePath = $imageDir . '/' . $imageIdentifier;

        // write the file to .tmp, so we can do an atomic rename later to avoid possibly serving partly written files
        $bytesWritten = file_put_contents($imagePath . '.tmp', $imageData);

        // if write failed or 0 bytes were written (0 byte input == fail), or we wrote less than expected
        if (!$bytesWritten || ($bytesWritten < strlen($imageData))) {
            throw new StorageException('Failed writing file to disk: ' . $imagePath, 507);
        }

        rename($imagePath . '.tmp', $imagePath);

        return true;
    }

    public function delete(string $user, string $imageIdentifier): bool
    {
        if (!$this->imageExists($user, $imageIdentifier)) {
            throw new StorageException('File not found', 404);
        }

        $path = $this->getImagePath($user, $imageIdentifier);

        return unlink($path);
    }

    public function getImage(string $user, string $imageIdentifier): ?string
    {
        if (!$this->imageExists($user, $imageIdentifier)) {
            throw new StorageException('File not found', 404);
        }

        $path = $this->getImagePath($user, $imageIdentifier);

        return file_get_contents($path) ?: null;
    }

    public function getLastModified(string $user, string $imageIdentifier): DateTime
    {
        if (!$this->imageExists($user, $imageIdentifier)) {
            throw new StorageException('File not found', 404);
        }

        $path = $this->getImagePath($user, $imageIdentifier);

        $timestamp = filemtime($path);

        return new DateTime('@' . $timestamp);
    }

    public function getStatus(): bool
    {
        return is_writable($this->dataDir);
    }

    public function imageExists(string $user, string $imageIdentifier): bool
    {
        $path = $this->getImagePath($user, $imageIdentifier);

        return file_exists($path);
    }

    /**
     * Get the path to an image
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier Image identifier
     * @return string
     */
    protected function getImagePath(string $user, string $imageIdentifier): string
    {
        $userPath = str_pad($user, 3, '0', STR_PAD_LEFT);
        $parts = [
            $this->dataDir,
            $userPath[0],
            $userPath[1],
            $userPath[2],
            $user,
            $imageIdentifier[0],
            $imageIdentifier[1],
            $imageIdentifier[2],
            $imageIdentifier,
        ];

        return implode(DIRECTORY_SEPARATOR, $parts);
    }
}
