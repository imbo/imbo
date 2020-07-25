<?php declare(strict_types=1);
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
     * @param bool $updateIfDuplicate Whether we should use an update statement if the image id exists, otherwise it'll result in an exception
     * @throws DatabaseException
     * @return bool Returns true on success or false on failure
     */
    function insertImage(string $user, string $imageIdentifier, Image $image, bool $updateIfDuplicate = true) : bool;

    /**
     * Delete an image from the database
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier Image identifier
     * @throws DatabaseException
     * @return bool Returns true on success or false on failure
     */
    function deleteImage(string $user, string $imageIdentifier) : bool;

    /**
     * Edit metadata
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier Image identifier
     * @param array $metadata An array with metadata
     * @throws DatabaseException
     * @return bool Returns true on success or false on failure
     */
    function updateMetadata(string $user, string $imageIdentifier, array $metadata) : bool;

    /**
     * Get all metadata associated with an image
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier Image identifier
     * @throws DatabaseException
     * @return array Returns the metadata as an array
     */
    function getMetadata(string $user, string $imageIdentifier) : array;

    /**
     * Delete all metadata associated with an image
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier Image identifier
     * @throws DatabaseException
     * @return bool Returns true on success or false on failure
     */
    function deleteMetadata(string $user, string $imageIdentifier);

    /**
     * Get images based on some query parameters
     *
     * This method is also responsible for setting a correct "hits" number in the images model.
     *
     * @param array $users The users which the images belongs to. If an empty array is specified
     *                     the adapter should return images for all users.
     * @param Query $query A query instance
     * @param Images $model The images model
     * @throws DatabaseException
     * @return array
     */
    function getImages(array $users, Query $query, Images $model) : array;

    /**
     * Load information from database into the image object
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier The image identifier
     * @param Image $image The image object to populate
     * @throws DatabaseException
     * @return bool
     */
    function load(string $user, string $imageIdentifier, Image $image) : bool;

    /**
     * Fetch image properties from the database
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier The image identifier
     * @return array{size: int, width: int, height: int, mime: string, extension: string, added: int, updated: int}
     */
    function getImageProperties(string $user, string $imageIdentifier) : array;

    /**
     * Get the last modified timestamp for given users
     *
     * Find the last modification timestamp of one or more users. If the image identifier parameter
     * is set the query will only look for that image in the set of users. If none of the specified
     * users have the image a 404 exception will be thrown. If the image identifier is skipped the
     * method will return either the current timestamp, or the max timestamp of any of the given
     * users.
     *
     * @param string[] $users The users
     * @param string $imageIdentifier The image identifier
     * @throws DatabaseException
     * @return DateTime Returns an instance of DateTime
     */
    function getLastModified(array $users, string $imageIdentifier = null) : DateTime;

    /**
     * Update the last modified timestamp for a given image to now.
     *
     * @param string $user The user the image belongs to
     * @param string $imageIdentifier The image identifier
     * @return DateTime The date the timestamp was updated to
     */
    function setLastModifiedNow(string $user, string $imageIdentifier) : DateTime;

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
    function setLastModifiedTime(string $user, string $imageIdentifier, DateTime $time) : DateTime;

    /**
     * Fetch the number of images, optionally filtered by a given user
     *
     * @param string $user The user which the images belongs to (pass null to count for all users)
     * @throws DatabaseException
     * @return int Returns the number of images
     */
    function getNumImages(string $user = null) : int;

    /**
     * Fetch the number of bytes stored, optionally filtered by a given user
     *
     * @param string $user The user which the images belongs to (pass null to count for all users)
     * @throws DatabaseException
     * @return int Returns the number of bytes
     */
    function getNumBytes(string $user = null) : int;

    /**
     * Fetch the number of users which has one or more images
     *
     * @throws DatabaseException
     * @return int Returns the number of users
     */
    function getNumUsers() : int;

    /**
     * Get the current status of the database connection
     *
     * This method is used with the status resource.
     *
     * @return bool
     */
    function getStatus() : bool;

    /**
     * Get the mime type of an image
     *
     * @param string $user The user which the image belongs to who owns the image
     * @param string $imageIdentifier The image identifier
     * @throws DatabaseException
     * @return string Returns the mime type of the image
     */
    function getImageMimeType(string $user, string $imageIdentifier) : string;

    /**
     * Check if an image already exists
     *
     * @param string $user The user which the image belongs to who owns the image
     * @param string $imageIdentifier The image identifier
     * @throws DatabaseException
     * @return bool Returns true of the image exists, false otherwise
     */
    function imageExists(string $user, string $imageIdentifier) : bool;

    /**
     * Insert a short URL
     *
     * @param string $shortUrlId The ID of the URL
     * @param string $user The user attached to the URL
     * @param string $imageIdentifier The image identifier attached to the URL
     * @param string $extension Optionl image extension
     * @param array<string, string> $query Optional query parameters
     * @return bool
     */
    function insertShortUrl(string $shortUrlId, string $user, string $imageIdentifier, string $extension = null, array $query = []) : bool;

    /**
     * Fetch the short URL identifier
     *
     * @param string $user The user attached to the URL
     * @param string $imageIdentifier The image identifier attached to the URL
     * @param string $extension Optionl image extension
     * @param array<string, string> $query Optional query parameters
     * @return ?string
     */
    function getShortUrlId(string $user, string $imageIdentifier, string $extension = null, array $query = []) : ?string;

    /**
     * Fetch parameters for a short URL
     *
     * @param string $shortUrlId The ID of the short URL
     * @return ?array<string, string> Returns an array with information regarding the short URL, or null if the
     *                    short URL is not found
     */
    function getShortUrlParams(string $shortUrlId) : ?array;

    /**
     * Delete short URLs attached to a specific image, or a single short URL
     *
     * @param string $user The user attached to the URL
     * @param string $imageIdentifier The image identifier attached to the URL
     * @param string $shortUrlId Specify to delete a single short URL for a specific image owned by
     *                           a user
     * @return bool
     */
    function deleteShortUrls(string $user, string $imageIdentifier, string $shortUrlId = null) : bool;

    /**
     * Return a list of the users present in the database
     *
     * @return string[]
     */
    function getAllUsers() : array;
}
