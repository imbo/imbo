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

/**
 * Storage driver interface
 *
 * This is an interface for different storage drivers for PHPIMS.
 *
 * @package PHPIMS
 * @subpackage Interfaces
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
interface PHPIMS_Storage_DriverInterface {
    /**
     * Store an image
     *
     * This method will take a temporary path (usually from the $_FILES array) and place it
     * somewhere suited for the actual storage driver. If an error occurs the driver should throw
     * an exception based on PHPIMS_Storage_Exception.
     *
     * @param string $hash The image hash
     * @param string $path Path to the temporary file
     * @return boolean Returns true on success or false on failure
     * @throws PHPIMS_Storage_Exception
     */
    public function store($hash, $path);

    /**
     * Delete an image
     *
     * This method will remove the file associated with $hash from the storage medium
     *
     * @param string $hash Unique hash identifying an image
     * @return boolean Returns true on success or false on failure
     * @throws PHPIMS_Storage_Exception
     */
    public function delete($hash);

    /**
     * Load the image identified by $hash
     *
     * The implementation of this method must fetch the content of the file identified by hash and
     * populate the blob property of $image.
     *
     * <code>
     * $image->setBlob(<data>);
     * </code>
     *
     * @param string $hash Unique hash identifying an image
     * @param PHPIMS_Image $image The image object
     * @return boolean Returns true on success or false on failure
     * @throws PHPIMS_Storage_Exception
     */
    public function load($hash, PHPIMS_Image $image);
}