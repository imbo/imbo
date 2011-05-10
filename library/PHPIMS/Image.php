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

namespace PHPIMS;

use \Imagine\Imagick\Imagine as Imagine;
use \Imagine\ImageInterface as ImagineImage;

/**
 * Class that represents a single image
 *
 * @package PHPIMS
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class Image {
    /**
     * Original filename
     *
     * @var string
     */
    private $filename;

    /**
     * Size of the file
     *
     * @var int
     */
    private $filesize;

    /**
     * Mime type of the image
     *
     * @var string
     */
    private $mimeType;

    /**
     * Extension of the file without the dot
     *
     * @var string
     */
    private $extension;

    /**
     * Blob containing the image itself
     *
     * @var string
     */
    private $blob;

    /**
     * The metadata attached to this image
     *
     * @var array
     */
    private $metadata = array();

    /**
     * Width of the image
     *
     * @var int
     */
    private $width;

    /**
     * Heigt of the image
     *
     * @var int
     */
    private $height;

    /**
     * Imagine image instance
     *
     * @var \Imagine\ImageInterface
     */
    private $imagineImage;

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
     * @return PHPIMS\Image
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
     * @return PHPIMS\Image
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
     * @return PHPIMS\Image
     */
    public function setMimeType($mimeType) {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * Get the extension
     *
     * @return string
     */
    public function getExtension() {
        return $this->extension;
    }

    /**
     * Set the extension
     *
     * @param string $extension The file extension
     * @return PHPIMS\Image
     */
    public function setExtension($extension) {
        $this->extension = $extension;

        return $this;
    }

    /**
     * Get the blob
     *
     * @return string
     */
    public function getBlob() {
        return $this->blob;
    }

    /**
     * Set the blob
     *
     * @param string $blob The binary data to set
     * @return PHPIMS\Image
     */
    public function setBlob($blob) {
        $this->blob = $blob;

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
     * @return PHPIMS\Image
     */
    public function setMetadata(array $metadata) {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * Get the width
     *
     * @return int
     */
    public function getWidth() {
        return $this->width;
    }

    /**
     * Set the width
     *
     * @param int $width Width in pixels
     * @return PHPIMS\Image
     */
    public function setWidth($width) {
        $this->width = (int) $width;

        return $this;
    }

    /**
     * Get the height
     *
     * @return int
     */
    public function getHeight() {
        return $this->height;
    }

    /**
     * Set the height
     *
     * @param int $height Height in pixels
     * @return PHPIMS\Image
     */
    public function setHeight($height) {
        $this->height = (int) $height;

        return $this;
    }

    /**
     * Get the imagine image
     *
     * @return \Imagine\ImageInterface
     */
    public function getImagineImage() {
        if ($this->imagineImage === null) {
            $imagine = new Imagine();
            $this->imagineImage = $imagine->load($this->getBlob());
        }

        return $this->imagineImage;
    }

    /**
     * Set the imagine image
     *
     * @param \Imagine\ImageInterface $image Image instance
     * @return PHPIMS\Image
     */
    public function setImagineImage(ImagineImage $image) {
        $this->imagineImage = $image;
        $this->refresh();

        return $this;
    }

    /**
     * Refresh some properties based on the imagine image instance
     *
     * @return PHPIMS\Image
     */
    public function refresh() {
        $size = $this->imagineImage->getSize();
        $blob = (string) $this->imagineImage;

        $this->setWidth($size->getWidth())
             ->setHeight($size->getHeight())
             ->setFilesize(strlen($blob))
             ->setBlob($blob);

        return $this;
    }
}