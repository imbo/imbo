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
     * @param string $publicKey The public key of the user
     * @param string $imageIdentifier Image identifier
     * @param Image $image The image to insert
     * @return boolean Returns true on success or false on failure
     * @throws DatabaseException
     */
    function insertImage($publicKey, $imageIdentifier, Image $image);

    /**
     * Delete an image from the database
     *
     * @param string $publicKey The public key of the user
     * @param string $imageIdentifier Image identifier
     * @return boolean Returns true on success or false on failure
     * @throws DatabaseException
     */
    function deleteImage($publicKey, $imageIdentifier);

    /**
     * Edit metadata
     *
     * @param string $publicKey The public key of the user
     * @param string $imageIdentifier Image identifier
     * @param array $metadata An array with metadata
     * @return boolean Returns true on success or false on failure
     * @throws DatabaseException
     */
    function updateMetadata($publicKey, $imageIdentifier, array $metadata);

    /**
     * Get all metadata associated with an image
     *
     * @param string $publicKey The public key of the user
     * @param string $imageIdentifier Image identifier
     * @return array Returns the metadata as an array
     * @throws DatabaseException
     */
    function getMetadata($publicKey, $imageIdentifier);

    /**
     * Delete all metadata associated with an image
     *
     * @param string $publicKey The public key of the user
     * @param string $imageIdentifier Image identifier
     * @return boolean Returns true on success or false on failure
     * @throws DatabaseException
     */
    function deleteMetadata($publicKey, $imageIdentifier);

    /**
     * Get images based on some query parameters
     *
     * This method is also responsible for setting a correct "hits" number in the images model.
     *
     * @param string $publicKey The public key of the user
     * @param Query $query A query instance
     * @param Images $model The images model
     * @return array
     * @throws DatabaseException
     */
    function getImages($publicKey, Query $query, Images $model);

    /**
     * Load information from database into the image object
     *
     * @param string $publicKey The public key of the user
     * @param string $imageIdentifier The image identifier
     * @param Image $image The image object to populate
     * @return boolean
     * @throws DatabaseException
     */
    function load($publicKey, $imageIdentifier, Image $image);

    /**
     * Fetch image properties from the database
     *
     * @param string $publicKey The public key
     * @param string $imageIdentifier The image identifier
     * @return array
     */
    function getImageProperties($publicKey, $imageIdentifier);

    /**
     * Get the last modified timestamp of a user
     *
     * If the $imageIdentifier parameter is set, return when that image was last updated. If not
     * set, return when the user last updated any image. If the user does not have any images
     * stored, return the current timestamp.
     *
     * @param string $publicKey The public key of the user
     * @param string $imageIdentifier The image identifier
     * @return DateTime Returns an instance of DateTime
     * @throws DatabaseException
     */
    function getLastModified($publicKey, $imageIdentifier = null);

    /**
     * Fetch the number of images owned by a given user
     *
     * @param string $publicKey The public key of the user
     * @return int Returns the number of images
     * @throws DatabaseException
     */
    function getNumImages($publicKey);

    /**
     * Fetch the number of bytes stored by a user
     *
     * @param string $publicKey The public key of the user
     * @return int Returns the number of bytes
     * @throws DatabaseException
     */
    function getNumBytes($publicKey);

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
     * @param string $publicKey The public key of the user who owns the image
     * @param string $imageIdentifier The image identifier
     * @return string Returns the mime type of the image
     * @throws DatabaseException
     */
    function getImageMimeType($publicKey, $imageIdentifier);

    /**
     * Check if an image already exists
     *
     * @param string $publicKey The public key of the user who owns the image
     * @param string $imageIdentifier The image identifier
     * @return boolean Returns true of the image exists, false otherwise
     * @throws DatabaseException
     */
    function imageExists($publicKey, $imageIdentifier);

    /**
     * Insert a short URL
     *
     * @param string $shortUrlId The ID of the URL
     * @param string $publicKey The public key attached to the URL
     * @param string $imageIdentifier The image identifier attached to the URL
     * @param string $extension Optionl image extension
     * @param array $query Optional query parameters
     * @return boolean
     */
    function insertShortUrl($shortUrlId, $publicKey, $imageIdentifier, $extension = null, array $query = array());

    /**
     * Fetch the short URL identifier
     *
     * @param string $publicKey The public key attached to the URL
     * @param string $imageIdentifier The image identifier attached to the URL
     * @param string $extension Optionl image extension
     * @param array $query Optional query parameters
     * @return string|null
     */
    function getShortUrlId($publicKey, $imageIdentifier, $extension = null, array $query = array());

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
     * @param string $publicKey The public key attached to the URL
     * @param string $imageIdentifier The image identifier attached to the URL
     * @param string $shortUrlId Specify to delete a single short URL for a specific image owned by
     *                           a user
     * @return boolean
     */
    function deleteShortUrls($publicKey, $imageIdentifier, $shortUrlId = null);
}
