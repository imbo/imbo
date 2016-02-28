<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Database;

use Imbo\Model\Image,
    Imbo\Model\Images,
    Imbo\Resource\Images\Query,
    Imbo\Exception\DatabaseException,
    DateTime;

/**
 * Database adapter interface
 *
 * This is an interface for storage adapters in Imbo.
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Database
 */
interface DatabaseInterface {
    /**
     * Insert a new image
     *
     * This method will insert a new image into the database. If the same image already exists,
     * just update the "updated" information.
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier Image identifier
     * @param Image $image The image to insert
     * @return boolean Returns true on success or false on failure
     * @throws DatabaseException
     */
    function insertImage($user, $imageIdentifier, Image $image);

    /**
     * Delete an image from the database
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier Image identifier
     * @return boolean Returns true on success or false on failure
     * @throws DatabaseException
     */
    function deleteImage($user, $imageIdentifier);

    /**
     * Edit metadata
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier Image identifier
     * @param array $metadata An array with metadata
     * @return boolean Returns true on success or false on failure
     * @throws DatabaseException
     */
    function updateMetadata($user, $imageIdentifier, array $metadata);

    /**
     * Get all metadata associated with an image
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier Image identifier
     * @return array Returns the metadata as an array
     * @throws DatabaseException
     */
    function getMetadata($user, $imageIdentifier);

    /**
     * Delete all metadata associated with an image
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier Image identifier
     * @return boolean Returns true on success or false on failure
     * @throws DatabaseException
     */
    function deleteMetadata($user, $imageIdentifier);

    /**
     * Get images based on some query parameters
     *
     * This method is also responsible for setting a correct "hits" number in the images model.
     *
     * @param array $users The users which the images belongs to
     * @param Query $query A query instance
     * @param Images $model The images model
     * @return array
     * @throws DatabaseException
     */
    function getImages(array $users, Query $query, Images $model);

    /**
     * Load information from database into the image object
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier The image identifier
     * @param Image $image The image object to populate
     * @return boolean
     * @throws DatabaseException
     */
    function load($user, $imageIdentifier, Image $image);

    /**
     * Fetch image properties from the database
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier The image identifier
     * @return array
     */
    function getImageProperties($user, $imageIdentifier);

    /**
     * Get the last modified timestamp for given users
     *
     * If the $imageIdentifier parameter is set, return when that image was last updated. If not
     * set, return the most recent date when one of the specified users last updated any image. If
     * the provided users does not have any images stored, return the current timestamp.
     *
     * @param array $users The users
     * @param string $imageIdentifier The image identifier
     * @return DateTime Returns an instance of DateTime
     * @throws DatabaseException
     */
    function getLastModified(array $users, $imageIdentifier = null);

    /**
     * Fetch the number of images, optionally filtered by a given user
     *
     * @param string $user The user which the images belongs to (pass null to count for all users)
     * @return int Returns the number of images
     * @throws DatabaseException
     */
    function getNumImages($user = null);

    /**
     * Fetch the number of bytes stored, optionally filtered by a given user
     *
     * @param string $user The user which the images belongs to (pass null to count for all users)
     * @return int Returns the number of bytes
     * @throws DatabaseException
     */
    function getNumBytes($user = null);

    /**
     * Fetch the number of users which has one or more images
     *
     * @return int Returns the number of users
     * @throws DatabaseException
     */
    function getNumUsers();

    /**
     * Get the current status of the database connection
     *
     * This method is used with the status resource.
     *
     * @return boolean
     */
    function getStatus();

    /**
     * Get the mime type of an image
     *
     * @param string $user The user which the image belongs to who owns the image
     * @param string $imageIdentifier The image identifier
     * @return string Returns the mime type of the image
     * @throws DatabaseException
     */
    function getImageMimeType($user, $imageIdentifier);

    /**
     * Check if an image already exists
     *
     * @param string $user The user which the image belongs to who owns the image
     * @param string $imageIdentifier The image identifier
     * @return boolean Returns true of the image exists, false otherwise
     * @throws DatabaseException
     */
    function imageExists($user, $imageIdentifier);

    /**
     * Insert a short URL
     *
     * @param string $shortUrlId The ID of the URL
     * @param string $user The user attached to the URL
     * @param string $imageIdentifier The image identifier attached to the URL
     * @param string $extension Optionl image extension
     * @param array $query Optional query parameters
     * @return boolean
     */
    function insertShortUrl($shortUrlId, $user, $imageIdentifier, $extension = null, array $query = []);

    /**
     * Fetch the short URL identifier
     *
     * @param string $user The user attached to the URL
     * @param string $imageIdentifier The image identifier attached to the URL
     * @param string $extension Optionl image extension
     * @param array $query Optional query parameters
     * @return string|null
     */
    function getShortUrlId($user, $imageIdentifier, $extension = null, array $query = []);

    /**
     * Fetch parameters for a short URL
     *
     * @param string $shortUrlId The ID of the short URL
     * @return array|null Returns an array with information regarding the short URL, or null if the
     *                    short URL is not found
     */
    function getShortUrlParams($shortUrlId);

    /**
     * Delete short URLs attached to a specific image, or a single short URL
     *
     * @param string $user The user attached to the URL
     * @param string $imageIdentifier The image identifier attached to the URL
     * @param string $shortUrlId Specify to delete a single short URL for a specific image owned by
     *                           a user
     * @return boolean
     */
    function deleteShortUrls($user, $imageIdentifier, $shortUrlId = null);
}
