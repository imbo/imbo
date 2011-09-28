<?php
/**
 * Imbo
 *
 * Copyright (c) 2011 Christer Edvartsen <cogo@starzinger.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * * The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package Imbo
 * @subpackage Interfaces
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */

namespace Imbo\Database;

use Imbo\Image\ImageInterface;
use Imbo\Resource\Images\Query;

/**
 * Database driver interface
 *
 * This is an interface for different database drivers.
 *
 * @package Imbo
 * @subpackage Interfaces
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */
interface DatabaseInterface {
    /**
     * Insert a new image
     *
     * This method will insert a new image into the database. On errors throw exceptions that
     * extends Imbo\Database\Exception.
     *
     * @param string $publicKey The public key of the user
     * @param string $imageIdentifier Image identifier
     * @param Imbo\Image\ImageInterface $image The image to insert
     * @return boolean Returns true on success or false on failure
     * @throws Imbo\Database\Exception
     */
    function insertImage($publicKey, $imageIdentifier, ImageInterface $image);

    /**
     * Delete an image from the database
     *
     * @param string $publicKey The public key of the user
     * @param string $imageIdentifier Image identifier
     * @return boolean Returns true on success or false on failure
     * @throws Imbo\Database\Exception
     */
    function deleteImage($publicKey, $imageIdentifier);

    /**
     * Edit metadata
     *
     * @param string $publicKey The public key of the user
     * @param string $imageIdentifier Image identifier
     * @param array $metadata An array with metadata
     * @return boolean Returns true on success or false on failure
     * @throws Imbo\Database\Exception
     */
    function updateMetadata($publicKey, $imageIdentifier, array $metadata);

    /**
     * Get all metadata associated with an image
     *
     * @param string $publicKey The public key of the user
     * @param string $imageIdentifier Image identifier
     * @return array Returns the metadata as an array
     * @throws Imbo\Database\Exception
     */
    function getMetadata($publicKey, $imageIdentifier);

    /**
     * Delete all metadata associated with an image
     *
     * @param string $publicKey The public key of the user
     * @param string $imageIdentifier Image identifier
     * @return boolean Returns true on success or false on failure
     * @throws Imbo\Database\Exception
     */
    function deleteMetadata($publicKey, $imageIdentifier);

    /**
     * Get images based on some query parameters
     *
     * @param string $publicKey The public key of the user
     * @param Imbo\Resource\Images\Query
     * @return array
     * @throws Imbo\Database\Exception
     */
    function getImages($publicKey, Query $query);

    /**
     * Load information from database into the image object
     *
     * @param string $publicKey The public key of the user
     * @param string $imageIdentifier The image identifier
     * @param Imbo\Image\ImageInterface $image The image object to populate
     * @return boolean
     * @throws Imbo\Database\Exception
     */
    function load($publicKey, $imageIdentifier, ImageInterface $image);
}
