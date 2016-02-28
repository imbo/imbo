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
    Imbo\Exception,
    DateTime,
    DateTimeZone;

/**
 * Filesystem storage driver
 *
 * This storage driver stores image files in a local filesystem.
 *
 * Configuration options supported by this driver:
 *
 * - <pre>(string) dataDir</pre> Absolute path to the base directory the images should be stored in
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Storage
 */
class Filesystem implements StorageInterface {
    /**
     * Parameters for the filesystem driver
     *
     * @var array
     */
    private $params = [
        'dataDir' => null,
    ];

    /**
     * Class constructor
     *
     * @param array $params Parameters for the driver
     */
    public function __construct(array $params) {
        $this->params = array_merge($this->params, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function store($user, $imageIdentifier, $imageData) {
        if (!is_writable($this->params['dataDir'])) {
            throw new StorageException('Could not store image', 500);
        }

        if ($this->imageExists($user, $imageIdentifier)) {
            return touch($this->getImagePath($user, $imageIdentifier));
        }

        $imageDir = $this->getImagePath($user, $imageIdentifier, false);
        $oldUmask = umask(0);

        if (!is_dir($imageDir)) {
            mkdir($imageDir, 0775, true);
        }

        umask($oldUmask);

        $imagePath = $imageDir . '/' . $imageIdentifier;

        $bytesWritten = file_put_contents($imagePath, $imageData);

        // if write failed or 0 bytes were written (0 byte input == fail), or we wrote less than expected
        if (!$bytesWritten || ($bytesWritten < strlen($imageData))) {
            throw new StorageException('Failed writing file (disk full? zero bytes input?) to disk: ' . $imagePath, 507);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($user, $imageIdentifier) {
        if (!$this->imageExists($user, $imageIdentifier)) {
            throw new StorageException('File not found', 404);
        }

        $path = $this->getImagePath($user, $imageIdentifier);

        return unlink($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getImage($user, $imageIdentifier) {
        if (!$this->imageExists($user, $imageIdentifier)) {
            throw new StorageException('File not found', 404);
        }

        $path = $this->getImagePath($user, $imageIdentifier);

        return file_get_contents($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getLastModified($user, $imageIdentifier) {
        if (!$this->imageExists($user, $imageIdentifier)) {
            throw new StorageException('File not found', 404);
        }

        $path = $this->getImagePath($user, $imageIdentifier);

        // Get the unix timestamp
        $timestamp = filemtime($path);

        // Create a new datetime instance
        return new DateTime('@' . $timestamp, new DateTimeZone('UTC'));
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus() {
        return is_writable($this->params['dataDir']);
    }

    /**
     * {@inheritdoc}
     */
    public function imageExists($user, $imageIdentifier) {
        $path = $this->getImagePath($user, $imageIdentifier);

        return file_exists($path);
    }

    /**
     * Get the path to an image
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier Image identifier
     * @param boolean $includeFilename Whether or not to include the last part of the path (the
     *                                 filename itself)
     * @return string
     */
    private function getImagePath($user, $imageIdentifier, $includeFilename = true) {
        $userPath = str_pad($user, 3, '0', STR_PAD_LEFT);
        $parts = [
            $this->params['dataDir'],
            $userPath[0],
            $userPath[1],
            $userPath[2],
            $user,
            $imageIdentifier[0],
            $imageIdentifier[1],
            $imageIdentifier[2],
        ];

        if ($includeFilename) {
            $parts[] = $imageIdentifier;
        }

        return implode(DIRECTORY_SEPARATOR, $parts);
    }
}
