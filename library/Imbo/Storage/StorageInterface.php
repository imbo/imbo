<?php
/**
 * Imbo
 *
 * Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
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
 * @package Interfaces
 * @subpackage Storage
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\Storage;

use Imbo\Image\ImageInterface;

/**
 * Storage driver interface
 *
 * This is an interface for different storage drivers for Imbo.
 *
 * @package Interfaces
 * @subpackage Storage
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
interface StorageInterface {
    /**
     * Store an image
     *
     * This method will receive the binary data of the image and store it somewhere suited for the
     * actual storage driver. If an error occurs the driver should throw an
     * Imbo\Exception\StorageException exception.
     *
     * @param string $publicKey The public key of the user
     * @param string $imageIdentifier The image identifier
     * @param string $imageData The image data to store
     * @return boolean Returns true on success or false on failure
     * @throws Imbo\Exception\StorageException
     */
    function store($publicKey, $imageIdentifier, $imageData);

    /**
     * Delete an image
     *
     * This method will delete the file associated with $imageIdentifier from the storage medium
     *
     * @param string $publicKey The public key of the user
     * @param string $imageIdentifier Image identifier
     * @return boolean Returns true on success or false on failure
     * @throws Imbo\Exception\StorageException
     */
    function delete($publicKey, $imageIdentifier);

    /**
     * Get image content
     *
     * @param string $publicKey The public key of the user
     * @param string $imageIdentifier Image identifier
     * @return string The binary content of the image
     * @throws Imbo\Exception\StorageException
     */
    function getImage($publicKey, $imageIdentifier);

    /**
     * Get the last modified timestamp
     *
     * @param string $publicKey The public key of the user
     * @param string $imageIdentifier Image identifier
     * @return DateTime Returns an instance of DateTime
     * @throws Imbo\Exception\StorageException
     */
    function getLastModified($publicKey, $imageIdentifier);

    /**
     * Get the current status of the storage
     *
     * This method is used with the status resource.
     *
     * @return boolean
     */
    function getStatus();
}
