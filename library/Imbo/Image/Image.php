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
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */

namespace Imbo\Image;

/**
 * Class that represents a single image
 *
 * @package Imbo
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */
class Image implements ImageInterface {
    /**
     * Supported mime types and the correct file extensions
     *
     * @var array
     */
    static public $mimeTypes = array(
        'image/png'  => 'png',
        'image/jpeg' => 'jpg',
        'image/gif'  => 'gif',
    );

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
     * @use Imbo\Image\ImageInterface::getFilesize()
     */
    public function getFilesize() {
        return $this->filesize;
    }

    /**
     * @use Imbo\Image\ImageInterface::getMimeType()
     */
    public function getMimeType() {
        return $this->mimeType;
    }

    /**
     * @use Imbo\Image\ImageInterface::setMimeType()
     */
    public function setMimeType($mimeType) {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * @use Imbo\Image\ImageInterface::getExtension()
     */
    public function getExtension() {
        return $this->extension;
    }

    /**
     * @use Imbo\Image\ImageInterface::setExtension()
     */
    public function setExtension($extension) {
        $this->extension = $extension;

        return $this;
    }

    /**
     * @use Imbo\Image\ImageInterface::getBlob()
     */
    public function getBlob() {
        return $this->blob;
    }

    /**
     * @use Imbo\Image\ImageInterface::setBlob()
     */
    public function setBlob($blob) {
        $this->blob = $blob;
        $this->filesize = strlen($blob);

        return $this;
    }

    /**
     * @use Imbo\Image\ImageInterface::getMetadata()
     */
    public function getMetadata() {
        return $this->metadata;
    }

    /**
     * @use Imbo\Image\ImageInterface::setMetadata()
     */
    public function setMetadata(array $metadata) {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * @use Imbo\Image\ImageInterface::getWidth()
     */
    public function getWidth() {
        return $this->width;
    }

    /**
     * @use Imbo\Image\ImageInterface::setWidth()
     */
    public function setWidth($width) {
        $this->width = (int) $width;

        return $this;
    }

    /**
     * @use Imbo\Image\ImageInterface::getHeight()
     */
    public function getHeight() {
        return $this->height;
    }

    /**
     * @use Imbo\Image\ImageInterface::setHeight()
     */
    public function setHeight($height) {
        $this->height = (int) $height;

        return $this;
    }

    /**
     * @use Imbo\Image\ImageInterface::supportedMimeType()
     */
    static public function supportedMimeType($mime) {
        return isset(self::$mimeTypes[$mime]);
    }

    /**
     * @use Imbo\Image\ImageInterface::getFileExtension()
     */
    static public function getFileExtension($mime) {
        return isset(self::$mimeTypes[$mime]) ? self::$mimeTypes[$mime] : false;
    }
}
