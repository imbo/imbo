<?php declare(strict_types=1);

namespace Imbo\EventListener\ImageVariations\Storage;

use FilesystemIterator;
use Imbo\Exception\StorageException;

use function dirname;

use const DIRECTORY_SEPARATOR;
use const STR_PAD_LEFT;

class Filesystem implements StorageInterface
{
    /**
     * Class constructor.
     *
     * @param string $baseDir Directory to store the files in
     */
    public function __construct(private string $baseDir)
    {
    }

    public function storeImageVariation(string $user, string $imageIdentifier, string $blob, int $width): void
    {
        if (!is_writable($this->baseDir)) {
            throw new StorageException('Could not store image variation (directory not writable)', 500);
        }

        $variationsPath = $this->getImagePath($user, $imageIdentifier, $width);
        $variationsDir = dirname($variationsPath);

        if (!is_dir($variationsDir)) {
            $oldUmask = umask(0);
            mkdir($variationsDir, 0775, true);
            umask($oldUmask);
        }

        $result = file_put_contents($variationsPath, $blob);

        if (false === $result) {
            throw new StorageException('Could not store image variation (write failed)', 500);
        }
    }

    public function getImageVariation(string $user, string $imageIdentifier, int $width): string
    {
        $variationPath = $this->getImagePath($user, $imageIdentifier, $width);

        if (!file_exists($variationPath)) {
            throw new StorageException('File not found', 404);
        }

        $blob = file_get_contents($variationPath);

        if (false === $blob) {
            throw new StorageException('Unable to get image variation', 500);
        }

        return $blob;
    }

    public function deleteImageVariations(string $user, string $imageIdentifier, ?int $width = null): void
    {
        $dir = $this->getImagePath($user, $imageIdentifier);

        if (!is_dir($dir)) {
            return;
        }

        $files = [];

        if (null !== $width) {
            $files[] = $this->getImagePath($user, $imageIdentifier, $width);
        } else {
            $files = glob($dir.'/*');

            if (false === $files) {
                return;
            }
        }

        foreach ($files as $file) {
            unlink($file);
        }

        if ($this->isDirectoryEmpty($dir)) {
            rmdir($dir);
        }
    }

    private function getImagePath(string $user, string $imageIdentifier, ?int $width = null): string
    {
        $userPath = str_pad($user, 3, '0', STR_PAD_LEFT);
        $parts = array_filter([
            $this->baseDir,
            $userPath[0],
            $userPath[1],
            $userPath[2],
            $user,
            $imageIdentifier[0],
            $imageIdentifier[1],
            $imageIdentifier[2],
            $imageIdentifier,
            $width,
        ]);

        return implode(DIRECTORY_SEPARATOR, $parts);
    }

    private function isDirectoryEmpty(string $path): bool
    {
        if (!is_dir($path)) {
            return true;
        }

        $iterator = new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS);

        return !$iterator->valid();
    }
}
