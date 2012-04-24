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
 * @package Storage
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\Storage;

use Imbo\Image\ImageInterface,
    Imbo\Exception\StorageException,
    Imbo\Exception,
    DateTime;

/**
 * Filesystem storage driver
 *
 * This storage driver stores image files in a local filesystem.
 *
 * Configuration options supported by this driver:
 *
 * - <pre>(string) dataDir</pre> Absolute path to the base directory the images should be stored in
 *
 * @package Storage
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class Filesystem implements StorageInterface {
    /**
     * Parameters for the filesystem driver
     *
     * @var array
     */
    private $params = array(
        'dataDir' => null,
    );

    /**
     * Class constructor
     *
     * @param array $params Parameters for the driver
     */
    public function __construct(array $params) {
        $this->params = array_merge($this->params, $params);
    }

    /**
     * @see Imbo\Storage\StorageInterface::store()
     */
    public function store($publicKey, $imageIdentifier, $imageData) {
        if (!is_writable($this->params['dataDir'])) {
            throw new StorageException('Could not store image', 500);
        }

        // Create path for the image
        $imageDir = $this->getImagePath($publicKey, $imageIdentifier, false);
        $oldUmask = umask(0);

        if (!is_dir($imageDir)) {
            mkdir($imageDir, 0775, true);
        }

        umask($oldUmask);

        $imagePath = $imageDir . '/' . $imageIdentifier;

        if (file_exists($imagePath)) {
            $e = new StorageException('Image already exists', 400);
            $e->setImboErrorCode(Exception::IMAGE_ALREADY_EXISTS);

            throw $e;
        }

        return (bool) file_put_contents($imagePath, $imageData);
    }

    /**
     * @see Imbo\Storage\StorageInterface::delete()
     */
    public function delete($publicKey, $imageIdentifier) {
        $path = $this->getImagePath($publicKey, $imageIdentifier);

        if (!is_file($path)) {
            throw new StorageException('File not found', 404);
        }

        return unlink($path);
    }

    /**
     * @see Imbo\Storage\StorageInterface::getImage()
     */
    public function getImage($publicKey, $imageIdentifier) {
        $path = $this->getImagePath($publicKey, $imageIdentifier);

        if (!is_file($path)) {
            throw new StorageException('File not found', 404);
        }

        return file_get_contents($path);
    }

    /**
     * @see Imbo\Storage\StorageInterface::getLastModified()
     */
    public function getLastModified($publicKey, $imageIdentifier) {
        $path = $this->getImagePath($publicKey, $imageIdentifier);

        if (!is_file($path)) {
            throw new StorageException('File not found', 404);
        }

        // Get the unix timestamp
        $timestamp = filemtime($path);

        // Create a new datetime instance
        return new DateTime('@' . $timestamp);
    }

    /**
     * @see Imbo\Storage\StorageInterface::getStatus()
     */
    public function getStatus() {
        return is_writable($this->params['dataDir']);
    }

    /**
     * Get the path to an image
     *
     * @param string $publicKey The key
     * @param string $imageIdentifier Image identifier
     * @param boolean $includeFilename Wether or not to include the last part of the path (the
     *                                 filename itself)
     * @return string
     */
    private function getImagePath($publicKey, $imageIdentifier, $includeFilename = true) {
        $parts = array(
            $this->params['dataDir'],
            $publicKey[0],
            $publicKey[1],
            $publicKey[2],
            $publicKey,
            $imageIdentifier[0],
            $imageIdentifier[1],
            $imageIdentifier[2],
        );

        if ($includeFilename) {
            $parts[] = $imageIdentifier;
        }

        return implode(DIRECTORY_SEPARATOR, $parts);
    }
}
