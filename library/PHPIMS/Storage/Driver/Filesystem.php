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
     * @see PHPIMS_Storage_Driver_Interface::store
     */
    public function store($path) {
        $params = $this->getParams();

        if (!is_writable($params['dataDir'])) {
            throw new PHPIMS_Storage_Exception('Could not store image', 500);
        }

        $image = $this->getOperation()->getImage();

        // Create path for the image
        $hash = $image->getHash();
        $imageDir = $params['dataDir'] . '/' . $hash[0] . '/' . $hash[1] . '/' . $hash[2];
        $oldUmask = umask(0);

        if (!is_dir($imageDir)) {
            mkdir($imageDir, 0755, true);
        }

        umask($oldUmask);

        $imagePath = $imageDir . '/' . $image->getHash();

        return move_uploaded_file($path, $imagePath);
    }

    /**
     * @see PHPIMS_Storage_Driver_Interface::delete
     */
    public function delete($hash) {
        $path = $this->getImagePath($hash);

        if (!is_file($path)) {
            throw new PHPIMS_Storage_Exception('File not found', 404);
        }

        return unlink($path);
    }

    /**
     * @see PHPIMS_Storage_Driver_Interface::load
     */
    public function load($hash) {
        $path = $this->getImagePath($hash);

        if (!is_file($path)) {
            throw new PHPIMS_Storage_Exception('File not found', 404);
        }

        $this->getOperation()->getResponse()->setRawData(file_get_contents($path));

        return true;
    }

    /**
     * Get the path to an image identified by $hash
     *
     * @param string $hash Unique hash identifying an image
     * @return string
     */
    public function getImagePath($hash) {
        $params = $this->getParams();
        $imagePath = $params['dataDir'] . '/' . $hash[0] . '/' . $hash[1] . '/' . $hash[2] . '/' . $hash;

        return $imagePath;
    }
}