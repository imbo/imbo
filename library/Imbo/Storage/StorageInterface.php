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

namespace Imbo\Storage;

use Imbo\Image\ImageInterface;

/**
 * Storage driver interface
 *
 * This is an interface for different storage drivers for Imbo.
 *
 * @package Imbo
 * @subpackage Interfaces
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */
interface StorageInterface {
    /**
     * Store an image
     *
     * This method will receive the binary data of the image place it somewhere suited for the
     * actual storage driver. If an error occurs the driver should throw an exception based on
     * Imbo\Storage\Exception.
     *
     * @param string $publicKey The public key of the user
     * @param string $imageIdentifier The image identifier
     * @param Imbo\Image\ImageInterface $image The image to store
     * @return boolean Returns true on success or false on failure
     * @throws Imbo\Storage\Exception
     */
    function store($publicKey, $imageIdentifier, ImageInterface $image);

    /**
     * Delete an image
     *
     * This method will delete the file associated with $imageIdentifier from the storage medium
     *
     * @param string $publicKey The public key of the user
     * @param string $imageIdentifier Image identifier
     * @return boolean Returns true on success or false on failure
     * @throws Imbo\Storage\Exception
     */
    function delete($publicKey, $imageIdentifier);

    /**
     * Load an image
     *
     * The implementation of this method must fetch the content of the file identified by
     * $imageIdentifier and populate the blob property of $image:
     *
     * <code>
     * $image->setBlob(<data>);
     * </code>
     *
     * @param string $publicKey The public key of the user
     * @param string $imageIdentifier Image identifier
     * @param Imbo\Image\ImageInterface $image The image object
     * @return boolean Returns true on success or false on failure
     * @throws Imbo\Storage\Exception
     */
    function load($publicKey, $imageIdentifier, ImageInterface $image);
}
