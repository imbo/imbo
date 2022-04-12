<?php declare(strict_types=1);
namespace Imbo\EventListener\ImageVariations\Storage;

/**
 * Storage adapter interface for the image variations event listener
 */
interface StorageInterface
{
    /**
     * Store an image variation
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier The image identifier of the original image
     * @param string $blob The image blob to store
     * @param int $width The width of the variation
     * @return bool
     */
    public function storeImageVariation(string $user, string $imageIdentifier, string $blob, int $width): bool;

    /**
     * Get the blob of an image variation
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier The image identifier of the original image
     * @param int $width The width of the variation
     * @return ?string
     */
    public function getImageVariation(string $user, string $imageIdentifier, int $width): ?string;

    /**
     * Remove an image variation
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier The image identifier of the original image
     * @param int $width Only delete the variation with this width
     * @return bool
     */
    public function deleteImageVariations(string $user, string $imageIdentifier, int $width = null): bool;
}
