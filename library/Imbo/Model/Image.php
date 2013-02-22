<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Model;

use DateTime;

/**
 * Image model
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Models
 */
class Image implements ModelInterface {
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
    private $metadata;

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
     * MD5 checksum of the image data
     *
     * @var string
     */
    private $checksum;

    /**
     * Flag used with image transformations
     *
     * @var boolean
     */
    private $transformed = false;

    /**
     * Added date
     *
     * @var DateTime
     */
    private $added;

    /**
     * Updated date
     *
     * @var DateTime
     */
    private $updated;

    /**
     * Public key
     *
     * @var string
     */
    private $publicKey;

    /**
     * Image identifier
     *
     * @var string
     */
    private $imageIdentifier;

    /**
     * Get the size of the image data in bytes
     *
     * @return int
     */
    public function getFilesize() {
        return $this->filesize;
    }

    /**
     * Set the size of the image in bytes
     *
     * @param int $size The size of the image
     * @return Image
     */
    public function setFilesize($size) {
        $this->filesize = (int) $size;

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
     * Set the blob and update filesize and checksum properties
     *
     * @param string $blob The binary data to set
     * @return Image
     */
    public function setBlob($blob) {
        $this->blob = $blob;
        $this->setFilesize(strlen($blob));
        $this->setChecksum(md5($blob));

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
     * Get the added date
     *
     * @return DateTime
     */
    public function getAddedDate() {
        return $this->added;
    }

    /**
     * Set the added date
     *
     * @param DateTime $added When the image was added
     * @return Image
     */
    public function setAddedDate(DateTime $added) {
        $this->added = $added;

        return $this;
    }

    /**
     * Get the updated date
     *
     * @return DateTime
     */
    public function getUpdatedDate() {
        return $this->updated;
    }

    /**
     * Set the updated date
     *
     * @param DateTime $updated When the image was updated
     * @return Image
     */
    public function setUpdatedDate(DateTime $updated) {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get the public key
     *
     * @return string
     */
    public function getPublicKey() {
        return $this->publicKey;
    }

    /**
     * Set the public key
     *
     * @param string $publicKey The public key
     * @return Image
     */
    public function setPublicKey($publicKey) {
        $this->publicKey = $publicKey;

        return $this;
    }

    /**
     * Get the image identifier
     *
     * @return string
     */
    public function getImageIdentifier() {
        return $this->imageIdentifier;
    }

    /**
     * Set the public key
     *
     * @param string $imageIdentifier The public key
     * @return Image
     */
    public function setImageIdentifier($imageIdentifier) {
        $this->imageIdentifier = $imageIdentifier;

        return $this;
    }

    /**
     * Get the checksum of the current image data
     *
     * @return string
     */
    public function getChecksum() {
        return $this->checksum;
    }

    /**
     * Set the checksum
     *
     * @param string $checksum The checksum to set
     * @return Image
     */
    public function setChecksum($checksum) {
        $this->checksum = $checksum;

        return $this;
    }

    /**
     * Update or get the transformed flag
     *
     * @param boolean $flag Set this to true or false to update the current flag. If not specified
     *                      this method will return the current value of this flag.
     * @return Image|boolean
     */
    public function hasBeenTransformed($flag = null) {
        if ($flag === null) {
            return $this->transformed;
        }

        $this->transformed = (boolean) $flag;

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
