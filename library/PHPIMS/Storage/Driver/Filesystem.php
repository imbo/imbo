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
 * @subpackage StorageDriver
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
 * @subpackage StorageDriver
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class PHPIMS_Storage_Driver_Filesystem extends PHPIMS_Storage_Driver_Abstract {
    /**
     * Store an image
     *
     * This method will take a temporary path (usually from the $_FILES array) and place it
     * somewhere suited for the actual storage driver. A Filesystem driver will just move the file
     * to the current data location. If an error occurs the driver should throw an exception based
     * on PHPIMS_Storage_Exception.
     *
     * @param string $path Path to the temporary file
     * @param PHPIMS_Image $image The image object
     * @return boolean Returns true on success or false on failure
     * @throws PHPIMS_Storage_Exception
     */
    public function store($path, PHPIMS_Image $image) {
        $params = $this->getParams();

        if (!is_writable($params['path'])) {
            throw new PHPIMS_Storage_Exception('Could not store image', 500);
        }

        $newPath = $params['path'] . '/' . $image->getId();
        $result = move_uploaded_file($path, $newPath);

        if ($result) {
            // @codeCoverageIgnoreStart
            // Update image path if the operation was successful
            $image->setPath($newPath);
        }
        // @codeCoverageIgnoreEnd

        return $result;
    }

    /**
     * Delete an image
     *
     * This method will remove the file associated with $hash from the storage medium
     *
     * @param string $hash Unique hash identifying an image
     * @return boolean Returns true on success or false on failure
     * @throws PHPIMS_Storage_Exception
     */
    public function delete($hash) {
        $params = $this->getParams();

        $file = $params['path'] . '/' . $hash;

        if (!is_file($file)) {
            throw new PHPIMS_Storage_Exception('File does not exist on the file system', 500);
        }

        return unlink($file);
    }

    /**
     * Fetch an image
     *
     * This method will return the image data as a blob based on the hash.
     *
     * @param string $hash Unique hash identifying an image
     * @return array
     * @throws PHPIMS_Storage_Exception
     */
    public function fetch($hash) {
        $params = $this->getParams();

        $file = $params['path'] . '/' . $hash;

        if (!is_file($file)) {
            throw new PHPIMS_Storage_Exception('File does not exist on the file system', 404);
        }

        return file_get_contents($file);
    }
}