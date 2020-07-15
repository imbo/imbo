<?php declare(strict_types=1);
namespace Imbo\Storage;

use DateTime;
use Imbo\Exception\StorageException;

/**
 * Storage adapter interface
 *
 * This is an interface for storage adapters in Imbo.
 */
interface StorageInterface {
    /**
     * Store an image
     *
     * This method will receive the binary data of the image and store it somewhere suited for the
     * actual storage adaper. If an error occurs the adapter should throw an
     * Imbo\Exception\StorageException exception.
     *
     * If the image already exists, overwrite it.
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier The image identifier
     * @param string $imageData The image data to store
     * @throws StorageException
     * @return bool Returns true on success or false on failure
     */
    function store(string $user, string $imageIdentifier, string $imageData) : bool;

    /**
     * Delete an image
     *
     * This method will delete the file associated with $imageIdentifier from the storage medium
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier Image identifier
     * @throws StorageException
     * @return bool Returns true on success or false on failure
     */
    function delete(string $user, string $imageIdentifier) : bool;

    /**
     * Get image content
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier Image identifier
     * @throws StorageException
     * @return ?string The binary content of the image, null on failure
     */
    function getImage(string $user, string $imageIdentifier) : ?string;

    /**
     * Get the last modified timestamp
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier Image identifier
     * @throws StorageException
     * @return DateTime Returns an instance of DateTime, using the UTC timezone
     */
    function getLastModified(string $user, string $imageIdentifier) : DateTime;

    /**
     * Get the current status of the storage
     *
     * This method is used with the status resource.
     *
     * @return bool
     */
    function getStatus() : bool;

    /**
     * Check if the image already exists
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier Image identifier
     * @throws StorageException
     * @return bool True if the image exists, false otherwise
     */
    function imageExists(string $user, string $imageIdentifier) : bool;
}
