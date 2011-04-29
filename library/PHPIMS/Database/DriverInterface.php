<?php
/**
 * PHPIMS
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
 * @package PHPIMS
 * @subpackage Interfaces
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Database;

use PHPIMS\Image;

/**
 * Database driver interface
 *
 * This is an interface for different database drivers.
 *
 * @package PHPIMS
 * @subpackage Interfaces
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
interface DriverInterface {
    /**
     * Insert a new image
     *
     * This method will insert a new image into the database. On errors throw exceptions that
     * extends PHPIMS\Database\Exception.
     *
     * @param string $imageIdentifier Image identifier
     * @param PHPIMS\Image $image The image to insert
     * @return boolean Returns true on success or false on failure
     * @throws PHPIMS\Database\Exception
     */
    public function insertImage($imageIdentifier, Image $image);

    /**
     * Delete an image from the database
     *
     * @param string $imageIdentifier Image identifier
     * @return boolean Returns true on success or false on failure
     * @throws PHPIMS\Database\Exception
     */
    public function deleteImage($imageIdentifier);

    /**
     * Edit metadata
     *
     * @param string $imageIdentifier Image identifier
     * @param array $metadata An array with metadata
     * @return boolean Returns true on success or false on failure
     * @throws PHPIMS\Database\Exception
     */
    public function updateMetadata($imageIdentifier, array $metadata);

    /**
     * Get all metadata associated with an image
     *
     * @param string $imageIdentifier Image identifier
     * @return array Returns the metadata as an array
     * @throws PHPIMS\Database\Exception
     */
    public function getMetadata($imageIdentifier);

    /**
     * Delete all metadata associated with an image
     *
     * @param string $imageIdentifier Image identifier
     * @return boolean Returns true on success or false on failure
     * @throws PHPIMS\Database\Exception
     */
    public function deleteMetadata($imageIdentifier);

    /**
     * Get images based on some query parameters
     *
     * @param int $page Page number. Defaults to 1
     * @param int $num Number of images to return. Defaults to 20.
     * @param boolean $metadata Wether or not to return metadata. Defaults to false
     * @param array $query Metadata to query
     * @param int $from Timestamp to fetch from
     * @param int $to Timestamp to fetch to
     * @return array
     * @throws PHPIMS\Database\Exception
     */
    public function getImages($page = 1, $num = 20, $metadata = false, array $query = array(), $from = null, $to = null);
}