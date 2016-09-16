<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Storage;

use Imbo\Exception\StorageException;

/**
 * Storage adapter interface
 *
 * This is an interface for storage adapters in Imbo.
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Storage
 */
interface StorageInterface {
    /**
     * Store an image
     *
     * This method will receive the binary data of the image and store it somewhere suited for the
     * actual storage adaper. If an error occurs the adapter should throw an
     * Imbo\Exception\StorageException exception.
     *
     * If the image already exists, simply overwrite it.
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier The image identifier
     * @param string $imageData The image data to store
     * @return boolean Returns true on success or false on failure
     * @throws StorageException
     */
    function store($user, $imageIdentifier, $imageData);

    /**
     * Delete an image
     *
     * This method will delete the file associated with $imageIdentifier from the storage medium
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier Image identifier
     * @return boolean Returns true on success or false on failure
     * @throws StorageException
     */
    function delete($user, $imageIdentifier);

    /**
     * Get image content
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier Image identifier
     * @return string The binary content of the image
     * @throws StorageException
     */
    function getImage($user, $imageIdentifier);

    /**
     * Get the last modified timestamp
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier Image identifier
     * @return DateTime Returns an instance of DateTime
     * @throws StorageException
     */
    function getLastModified($user, $imageIdentifier);

    /**
     * Get the current status of the storage
     *
     * This method is used with the status resource.
     *
     * @return boolean
     */
    function getStatus();

    /**
     * See if the image already exists
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier Image identifier
     * @return DateTime Returns an instance of DateTime
     * @throws StorageException
     */
    function imageExists($user, $imageIdentifier);
}
