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
     * This is the ID from the underlying database driver
     *
     * @var mixed
     */
    protected $id = null;

    /**
     * MD5 hash identifying the image. This is the value that PHPIMS uses to identify the image.
     * The hash is an MD5 of the file itself.
     *
     * @var string
     */
    protected $hash = null;

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
     * Mime type of the image
     *
     * @var string
     */
    protected $mimeType = null;

    /**
     * The metadata attached to this image
     *
     * @var array
     */
    protected $metadata = array();

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
     * Get the hash
     *
     * @return string
     */
    public function getHash() {
        return $this->hash;
    }

    /**
     * Set the hash
     *
     * @param string $hash Hash to set
     * @return PHPIMS_Image
     */
    public function setHash($hash) {
        $this->hash = $hash;

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
     * Get the mime type
     *
     * @return string
     */
    public function getMimeType() {
        return $this->mimeType;
    }

    /**
     * Set the mime type
     *
     * @param string $mimeType The mime type, for instance "image/png"
     * @return PHPIMS_Image
     */
    public function setMimeType($mimeType) {
        $this->mimeType = $mimeType;

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
     * @param array $metadata An array with metadata
     * @return PHPIMS_Image
     */
    public function setMetadata(array $metadata) {
        $this->metadata = $metadata;

        return $this;
    }
}