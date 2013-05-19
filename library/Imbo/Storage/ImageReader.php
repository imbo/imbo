<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Storage;

use Imbo\Exception\StorageException,
    Imbo\Exception;

/**
 * Image reader
 *
 * This abstraction layer provides read-only access to images within a single public key
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Storage
 */
class ImageReader {
    /**
     * Public key
     *
     * @var string
     */
    private $publicKey;

    /**
     * Storage instance
     * 
     * @var StorageInterface
     */
    private $storage;

    /**
     * Class constructor
     *
     * @param string $publicKey Public key
     */
    public function __construct($publicKey, StorageInterface $storage) {
        $this->publicKey = $publicKey;
        $this->storage = $storage;
    }

    /**
     * Get image content
     * 
     * @param  string $imageIdentifier Image identifier
     * @return string The binary content of the image
     * @throws StorageException
     */
    public function getImage($imageIdentifier) {
        return $this->storage->getImage($this->publicKey, $imageIdentifier);
    }

    /**
     * See if an image identifier exists in storage
     * 
     * @param  string $imageIdentifier Image identifier
     * @return boolean Returns true if image exists, false otherwise
     */
    public function imageExists($imageIdentifier) {
        return $this->storage->imageExists($this->publicKey, $imageIdentifier);
    }
}
