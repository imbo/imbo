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
 * @package Image
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\Image;

/**
 * Class that represents a single image
 *
 * @package Image
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class Image {
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
     * Get the size of the image data in bytes
     *
     * @return int
     */
    public function getFilesize() {
        return $this->filesize;
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
     * @return Image
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
     * @return Image
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
     * This method will also update the size property of the image (returned by getFileSize()).
     *
     * @param string $blob The binary data to set
     * @return Image
     */
    public function setBlob($blob) {
        $this->blob = $blob;
        $this->filesize = strlen($blob);

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
     * @return Image
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
     * @return Image
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
     * @return Image
     */
    public function setHeight($height) {
        $this->height = (int) $height;

        return $this;
    }

    /**
     * Check if a mime type is supported by Imbo
     *
     * @param string $mime The mime type to check. For instance "image/png"
     * @return boolean
     */
    static public function supportedMimeType($mime) {
        return isset(self::$mimeTypes[$mime]);
    }

    /**
     * Get the file extension mapped to a mime type
     *
     * @param string $mime The mime type. For instance "image/png"
     * @return boolean|string The extension (without the leading dot) on success or boolean false
     *                        if the mime type is not supported.
     */
    static public function getFileExtension($mime) {
        return isset(self::$mimeTypes[$mime]) ? self::$mimeTypes[$mime] : false;
    }
}
