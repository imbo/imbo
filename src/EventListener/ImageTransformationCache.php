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
    Imbo\Http\Request\Request,
    Imbo\Model\Image,
    Imbo\Exception\StorageException,
    Imbo\Exception\InvalidArgumentException,
    Symfony\Component\HttpFoundation\ResponseHeaderBag,
    RecursiveDirectoryIterator,
    RecursiveIteratorIterator;

/**
 * Image transformation cache
 *
 * Event listener that stores transformed images to disk. By using this listener Imbo will only
 * have to generate each transformation once. The listener is also responsible for deleting images
 * from the cache when the original images are deleted through the API.
 *
 * The values used to generate the unique cache key for each image are:
 *
 * - user
 * - image identifier
 * - normalized accept header
 * - image extension (can be null)
 * - image transformations (can be null)
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Event\Listeners
 */
class ImageTransformationCache implements ListenerInterface {
    /**
     * Root path where the cached images can be stored
     *
     * @var string
     */
    private $path;

    /**
     * Whether or not this request hit a cached version
     *
     * @var boolean
     */
    private $cacheHit = false;

    /**
     * Class constructor
     *
     * @param array $params Parameters for the cache
     * @throws InvalidArgumentException Throws an exception if the specified path is not writable
     */
    public function __construct(array $params) {
        if (!isset($params['path'])) {
            throw new InvalidArgumentException(
                'The image transformation cache path is missing from the configuration',
                500
            );
        }

        $path = $params['path'];

        if (!$this->isWritable($path)) {
            throw new InvalidArgumentException(
                'Image transformation cache path is not writable by the webserver: ' . $path,
                500
            );
        }

        $this->path = $path;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            // Look for images in the cache before transformations occur
            'image.get' => ['loadFromCache' => 20],

            // Store images in the cache before they are sent to the user agent
            'response.send' => ['storeInCache' => 10],

            // Remove from the cache when an image is deleted from Imbo
            'image.delete' => ['deleteFromCache' => 10],
        ];
    }

    /**
     * Load transformed images from the cache
     *
     * @param EventInterface $event The current event
     */
    public function loadFromCache(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();

        // Generate the full file path to the response
        $path = $this->getCacheFilePath($request);

        if (is_file($path)) {
            $data = @unserialize(file_get_contents($path));

            // Make sure the data from the cache is valid
            if (
                is_array($data) &&
                isset($data['image']) &&
                isset($data['headers']) &&
                ($data['image'] instanceof Image) &&
                ($data['headers'] instanceof ResponseHeaderBag)
            ) {
                // Mark as cache hit
                $data['headers']->set('X-Imbo-TransformationCache', 'Hit');
                $data['image']->hasBeenTransformed(false);

                // Replace all headers and set the image model
                $response->headers = $data['headers'];
                $response->setModel($data['image']);

                // Stop other listeners on this event
                $event->stopPropagation();

                // Mark this as a cache hit to prevent us from re-writing the result
                $this->cacheHit = true;

                return;
            } else {
                // Invalid data in the cache, delete the file
                unlink($path);
            }
        }

        // Mark as cache miss
        $response->headers->set('X-Imbo-TransformationCache', 'Miss');
    }

    /**
     * Store transformed images in the cache
     *
     * @param EventInterface $event The current event
     */
    public function storeInCache(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $model = $response->getModel();

        if (!$model instanceof Image || $this->cacheHit) {
            // Only store images in the cache, and don't try to rewrite on cache hit
            return;
        }

        $path = $this->getCacheFilePath($request);
        $dir = dirname($path);

        // Prepare data for the data
        $data = serialize([
            'image' => $model,
            'headers' => $response->headers,
        ]);

        // Create directory if it does not already exist. The last is_dir is there because race
        // conditions can occur, and another process could already have created the directory after
        // the first is_dir($dir) is called, causing the mkdir() to fail, and as a result of that
        // the image would not be stored in the cache. The error supressing is ghetto, I know, but
        // thats how we be rolling.
        //
        // "What?! Did you forget to is_dir()-guard it?" - Mats Lindh
        if (is_dir($dir) || @mkdir($dir, 0775, true) || is_dir($dir)) {
            $tmpPath = $path. '.tmp';

            // If in the middle of a cache write operation, fall back
            if (file_exists($tmpPath) || file_exists($path)) {
                return;
            }

            // Write the transformed image to a temporary location
            if (file_put_contents($tmpPath, $data)) {
                // Move the transformed image to the correct destination

                // We have to silence this in case race-conditions lead to source not existing,
                // in which case it'll give a warning (we'd use try/catch here in case of PHP7)
                if (@rename($path. '.tmp', $path) === false && !file_exists($path)) {
                    throw new StorageException(
                        'An error occured while moving transformed image to cache'
                    );
                }
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
        $cacheDir = $this->getCacheDir($request->getUser(), $request->getImageIdentifier());

        if (is_dir($cacheDir)) {
            $this->rmdir($cacheDir);
        }
    }

    /**
     * Get the path to the current image cache dir
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier The image identifier
     * @return string Returns the absolute path to the image cache dir
     */
    private function getCacheDir($user, $imageIdentifier) {
        $userPath = str_pad($user, 3, '0', STR_PAD_LEFT);
        return sprintf(
            '%s/%s/%s/%s/%s/%s/%s/%s/%s',
            $this->path,
            $userPath[0],
            $userPath[1],
            $userPath[2],
            $user,
            $imageIdentifier[0],
            $imageIdentifier[1],
            $imageIdentifier[2],
            $imageIdentifier
        );
    }

    /**
     * Get the absolute path to response in the cache
     *
     * @param Request $request The current request instance
     * @return string Returns the absolute path to the cache file
     */
    private function getCacheFilePath(Request $request) {
        $hash = $this->getCacheKey($request);
        $dir = $this->getCacheDir($request->getUser(), $request->getImageIdentifier());

        return sprintf(
            '%s/%s/%s/%s/%s',
            $dir,
            $hash[0],
            $hash[1],
            $hash[2],
            $hash
        );
    }

    /**
     * Generate a cache key
     *
     * @param Request $request The current request instance
     * @return string Returns a string that can be used as a cache key for the current image
     */
    private function getCacheKey(Request $request) {
        $user = $request->getUser();
        $imageIdentifier = $request->getImageIdentifier();
        $accept = $request->headers->get('Accept', '*/*');

        $accept = array_filter(explode(',', $accept), function(&$value) {
            // Trim whitespace
            $value = trim($value);

            // Remove optional params
            $pos = strpos($value, ';');

            if ($pos !== false) {
                $value = substr($value, 0, $pos);
            }

            // Keep values starting with "*/" or "image/"
            return ($value[0] === '*' && $value[1] === '/') || substr($value, 0, 6) === 'image/';
        });

        // Sort the remaining values
        sort($accept);

        $accept = implode(',', $accept);

        $extension = $request->getExtension();
        $transformations = $request->query->get('t');

        if (!empty($transformations)) {
            $transformations = implode('&', $transformations);
        }

        return md5($user . $imageIdentifier . $accept . $extension . $transformations);
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

    /**
     * Check whether or not a directory (or its parent) is writable
     *
     * @param string $path The path to check
     * @return boolean
     */
    private function isWritable($path) {
        if (!is_dir($path)) {
            // Path does not exist, check parent
            return $this->isWritable(dirname($path));
        }

        // Dir exists, check if it's writable
        return is_writable($path);
    }
}
