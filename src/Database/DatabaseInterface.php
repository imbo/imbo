<?php
namespace Imbo\Database;

use Imbo\Model\Image;
use Imbo\Model\Images;
use Imbo\Resource\Images\Query;
use Imbo\Exception\DatabaseException;
use DateTime;

/**
 * Database adapter interface
 *
 * This is an interface for storage adapters in Imbo.
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
     * @param boolean $updateIfDuplicate Whether we should use an update statement if the image id exists, otherwise it'll result in an exception
     * @return boolean Returns true on success or false on failure
     * @throws DatabaseException
     */
    function insertImage($user, $imageIdentifier, Image $image, $updateIfDuplicate = true);

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
     * @param array $users The users which the images belongs to. If an empty array is specified
     *                     the adapter should return images for all users.
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
     * Find the last modification timestamp of one or more users. If the image identifier parameter
     * is set the query will only look for that image in the set of users. If none of the specified
     * users have the image a 404 exception will be thrown. If the image identifier is skipped the
     * method will return either the current timestamp, or the max timestamp of any of the given
     * users.
     *
     * @param array $users The users
     * @param string $imageIdentifier The image identifier
     * @return DateTime Returns an instance of DateTime
     * @throws DatabaseException
     */
    function getLastModified(array $users, $imageIdentifier = null);

    /**
     * Update the last modified timestamp for a given image to now.
     *
     * @param string $user The user the image belongs to
     * @param string $imageIdentifier The image identifier
     * @return DateTime The date the timestamp was updated to
     */
    function setLastModifiedNow($user, $imageIdentifier);

    /**
     * Update the last modified timestamp for a given image
     *
     * Will find and modify the last modified timestamp for an image belonging
     * to a certain user to the given timestamp.
     *
     * @param string $user The user the image belongs to
     * @param string $imageIdentifier The image identifier
     * @param DateTime $time The timestamp to set last modified to
     * @return DateTime The date the timestamp was updated to
     */
    function setLastModifiedTime($user, $imageIdentifier, DateTime $time);

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

    /**
     * Return a list of the users present in the database
     *
     * @return string[]
     */
    function getAllUsers();
}
