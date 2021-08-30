<?php
namespace Imbo\EventListener\ImageVariations\Database;

use Imbo\Exception\DatabaseException;

/**
 * Database adapter interface for the image variations event listener
 */
interface DatabaseInterface {
    /**
     * Store an image variation
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier The image identifier of the original
     * @param int $width The width of the variation
     * @param int $height The height of the variation
     * @throws DatabaseException
     * @return bool
     */
    function storeImageVariationMetadata(string $user, string $imageIdentifier, int $width, int $height): bool;

    /**
     * Fetch the best match of an image
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier The image identifier of the original
     * @param int $width The width we want to resize the image to
     * @return ?array{width:int,height:int} Returns the closest width, or null
     */
    function getBestMatch(string $user, string $imageIdentifier, int $width): ?array;

    /**
     * Remove all metadata about image variations for an image
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier The image identifier
     * @param int $width Only delete the variation with this width
     * @return bool
     */
    function deleteImageVariations(string $user, string $imageIdentifier, int $width = null): bool;
}
