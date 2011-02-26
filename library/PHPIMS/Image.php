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
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

/**
 * Class that represents a single image
 *
 * @package PHPIMS
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class PHPIMS_Image {
    /**
     * ID of the image
     *
     * Unique identifier. Type depends on the underlying database driver. If
     * PHPIMS_Database_Driver_MongoDB is used the value will be a 24 byte string, and if
     * PHPIMS_Database_Driver_MySQL is used it will be an integer.
     *
     * @var mixed
     */
    protected $id = null;

    /**
     * Original filename
     *
     * @var string
     */
    protected $filename = null;

    /**
     * Size of the file
     *
     * @var int
     */
    protected $filesize = null;

    /**
     * The metadata attached to this image
     *
     * @var array An array of PHPIMS_Image_Metadata objects
     */
    protected $metadata = null;

    /**
     * Get the ID
     *
     * @return mixed
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set the ID
     *
     * @param mixed $id Unique ID to set
     * @return PHPIMS_Image
     */
    public function setId($id) {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the filename
     *
     * @return string
     */
    public function getFilename() {
        return $this->filename;
    }

    /**
     * Set the filename
     *
     * @param string $filename The original name of the image
     * @return PHPIMS_Image
     */
    public function setFilename($filename) {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get the size in bytes
     *
     * @return int
     */
    public function getFilesize() {
        return $this->filesize;
    }

    /**
     * Set the size in bytes
     *
     * @param int $filesize The size to set
     * @return PHPIMS_Image
     */
    public function setFilesize($filesize) {
        $this->filesize = $filesize;

        return $this;
    }

    /**
     * Get the metadata
     *
     * @return array
     */
    public function getMetadata() {
        return $this->metadata;
    }

    /**
     * Set the metadata
     *
     * @param array $metadata An array of PHPIMS_Image_Metadata objects
     * @return PHPIMS_Image
     */
    public function setMetadata(array $metadata) {
        $this->metadata = $metadata;

        return $this;
    }
}