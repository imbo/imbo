<?php declare(strict_types=1);
namespace Imbo\EventListener\ImageVariations\Storage;

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

    public function storeImageVariation(string $user, string $imageIdentifier, string $blob, int $width): bool
    {
        if (!is_writable($this->dataDir)) {
            throw new StorageException('Could not store image variation (directory not writable)', 500);
        }

        $variationsPath = $this->getImagePath($user, $imageIdentifier, $width);
        $variationsDir = dirname($variationsPath);

        if (!is_dir($variationsDir)) {
            $oldUmask = umask(0);
            mkdir($variationsDir, 0775, true);
            umask($oldUmask);
        }

        return (bool) file_put_contents($variationsPath, $blob);
    }

    public function getImageVariation(string $user, string $imageIdentifier, int $width): ?string
    {
        $variationPath = $this->getImagePath($user, $imageIdentifier, $width);

        if (file_exists($variationPath)) {
            return (string) file_get_contents($variationPath);
        }

        return null;
    }

    public function deleteImageVariations(string $user, string $imageIdentifier, ?int $width = null): bool
    {
        if (null !== $width) {
            return unlink($this->getImagePath($user, $imageIdentifier, $width));
        }

        $variationsPath = $this->getImagePath($user, $imageIdentifier);

        if (!is_dir($variationsPath)) {
            return false;
        }

        /** @var list<string> */
        $files = glob($variationsPath . '/*');

        foreach ($files as $file) {
            unlink($file);
        }

        return rmdir($variationsPath);
    }

    /**
     * Get the path to an image
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier Image identifier
     * @param int $width Width of the image, in pixels
     * @return string
     */
    private function getImagePath(string $user, string $imageIdentifier, ?int $width = null): string
    {
        $userPath = str_pad($user, 3, '0', STR_PAD_LEFT);
        $parts = array_filter([
            $this->dataDir,
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
}
