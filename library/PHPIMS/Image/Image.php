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

namespace PHPIMS\Image;

/**
 * Class that represents a single image
 *
 * @package PHPIMS
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class Image implements ImageInterface {
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
     * @use PHPIMS\Image\ImageInterface::getFilesize()
     */
    public function getFilesize() {
        return $this->filesize;
    }

    /**
     * @use PHPIMS\Image\ImageInterface::getMimeType()
     */
    public function getMimeType() {
        return $this->mimeType;
    }

    /**
     * @use PHPIMS\Image\ImageInterface::setMimeType()
     */
    public function setMimeType($mimeType) {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * @use PHPIMS\Image\ImageInterface::getExtension()
     */
    public function getExtension() {
        return $this->extension;
    }

    /**
     * @use PHPIMS\Image\ImageInterface::setExtension()
     */
    public function setExtension($extension) {
        $this->extension = $extension;

        return $this;
    }

    /**
     * @use PHPIMS\Image\ImageInterface::getBlob()
     */
    public function getBlob() {
        return $this->blob;
    }

    /**
     * @use PHPIMS\Image\ImageInterface::setBlob()
     */
    public function setBlob($blob) {
        $this->blob = $blob;
        $this->filesize = strlen($blob);

        return $this;
    }

    /**
     * @use PHPIMS\Image\ImageInterface::getMetadata()
     */
    public function getMetadata() {
        return $this->metadata;
    }

    /**
     * @use PHPIMS\Image\ImageInterface::setMetadata()
     */
    public function setMetadata(array $metadata) {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * @use PHPIMS\Image\ImageInterface::getWidth()
     */
    public function getWidth() {
        return $this->width;
    }

    /**
     * @use PHPIMS\Image\ImageInterface::setWidth()
     */
    public function setWidth($width) {
        $this->width = (int) $width;

        return $this;
    }

    /**
     * @use PHPIMS\Image\ImageInterface::getHeight()
     */
    public function getHeight() {
        return $this->height;
    }

    /**
     * @use PHPIMS\Image\ImageInterface::setHeight()
     */
    public function setHeight($height) {
        $this->height = (int) $height;

        return $this;
    }
}
