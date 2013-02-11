<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface,
    Imbo\Model\Image,
    RecursiveDirectoryIterator,
    RecursiveIteratorIterator;

/**
 * Image transformation cache
 *
 * Event listener that stores transformed images to disk. By using this listener Imbo will only
 * have to generate each transformation once. Requests for original images (no t[] in the URI)
 * will not be cached. The listener will also delete images from the cache when they are deleted
 * from Imbo.
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Event\Listeners
 */
class ImageTransformationCache implements ListenerInterface {
    /**
     * Root path where the temp. images can be stored
     *
     * @var string
     */
    private $path;

    /**
     * Class constructor
     *
     * @param string $path Path to store the temp. images
     */
    public function __construct($path) {
        $this->path = rtrim($path, '/');
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition() {
        return array(
            // Look for images in the cache before transformations occur
            new ListenerDefinition('image.transform', array($this, 'loadFromCache'), 20),

            // Store images in the cache after transformations has occured
            new ListenerDefinition('image.transform', array($this, 'storeInCache'), -20),

            // Remove from the cache when an image is deleted from Imbo
            new ListenerDefinition('image.delete', array($this, 'deleteFromCache'), 10),
        );
    }

    /**
     * Load transformed images from the cache
     *
     * @param EventInterface $event The current event
     */
    public function loadFromCache(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $transformations = $request->getQuery()->get('t');

        if (!$transformations) {
            // No transformations exist in the query, nothing to load
            return;
        }

        $image = $response->getImage();

        // Generate cache key and fetch the full path of the cached response
        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();

        $hash = $this->getCacheKey($publicKey, $imageIdentifier, $transformations);
        $fullPath = $this->getCacheFilePath($publicKey, $imageIdentifier, $hash);

        if (is_file($fullPath)) {
            $image = @unserialize(file_get_contents($fullPath));

            if ($image instanceof Image) {
                $response->setImage($image);
                $response->getHeaders()->set('X-Imbo-TransformationCache', 'Hit');

                $event->stopPropagation(true);

                return;
            } else {
                // Invalid data in the cache, delete the file
                unlink($fullPath);
            }
        }

        $response->getHeaders()->set('X-Imbo-TransformationCache', 'Miss');
    }

    /**
     * Store transformed images in the cache
     *
     * @param EventInterface $event The current event
     */
    public function storeInCache(EventInterface $event) {
        $response = $event->getResponse();
        $image = $response->getImage();

        if (!$image->hasBeenTransformed()) {
            // We only want to put images that have been transformed in the cache
            return;
        }

        $request = $event->getRequest();
        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();
        $transformations = $event->getRequest()->getQuery()->get('t');

        $hash = $this->getCacheKey($publicKey, $imageIdentifier, $transformations);
        $fullPath = $this->getCacheFilePath($publicKey, $imageIdentifier, $hash);

        $dir = dirname($fullPath);

        if (is_dir($dir) || mkdir($dir, 0775, true)) {
            if (file_put_contents($fullPath . '.tmp', serialize($image))) {
                rename($fullPath . '.tmp', $fullPath);
            }
        }
    }

    /**
     * Delete cached images from the cache
     *
     * @param EventInterface $event The current event
     */
    public function deleteFromCache(EventInterface $event) {
        $request = $event->getRequest();
        $cacheDir = $this->getCacheDir($request->getPublicKey(), $request->getImageIdentifier());

        if (is_dir($cacheDir)) {
            $this->rmdir($cacheDir);
        }
    }

    /**
     * Get the path to the current image cache dir
     *
     * @param string $publicKey The public key
     * @param string $imageIdentifier The image identifier
     * @return string Returns the absolute path to the image cache dir
     */
    private function getCacheDir($publicKey, $imageIdentifier) {
        return sprintf(
            '%s/%s/%s/%s/%s/%s/%s/%s/%s',
            $this->path,
            $publicKey[0],
            $publicKey[1],
            $publicKey[2],
            $publicKey,
            $imageIdentifier[0],
            $imageIdentifier[1],
            $imageIdentifier[2],
            $imageIdentifier
        );
    }

    /**
     * Get the absolute path to cache file
     *
     * @param string $publicKey The public key
     * @param string $imageIdentifier The image identifier
     * @param string $hash The hash used as cache key
     * @return string Returns the absolute path to the cache file
     */
    private function getCacheFilePath($publicKey, $imageIdentifier, $hash) {
        return sprintf(
            '%s/%s/%s/%s/%s',
            $this->getCacheDir($publicKey, $imageIdentifier),
            $hash[0],
            $hash[1],
            $hash[2],
            $hash
        );
    }

    /**
     * Generate a cache key
     *
     * @param string $publicKey The public key
     * @param string $imageIdentifier The image identifier
     * @param array $transformations The transformations as specified in the URL
     * @return string Returns a string that can be used as a cache key for the current image
     */
    private function getCacheKey($publicKey, $imageIdentifier, array $transformations) {
        return hash(
            'sha256',
            $publicKey . '|' .
            $imageIdentifier . '|' .
            join(',', $transformations)
        );
    }

    /**
     * Completely remove a directory (with contents)
     *
     * @param string $dir Name of a directory
     */
    private function rmdir($dir) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            $name = $file->getPathname();

            if (substr($name, -1) === '.') {
                continue;
            }

            if ($file->isDir()) {
                // Remove dir
                rmdir($name);
            } else {
                // Remove file
                unlink($name);
            }
        }

        // Remove the directory itself
        rmdir($dir);
    }
}
