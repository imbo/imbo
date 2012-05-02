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
 * @package EventListener
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\EventListener;

use Imbo\Exception\RuntimeException,
    Imbo\EventManager\EventInterface,
    Imbo\Http\ContentNegotiation,
    Imbo\Image\Image;

/**
 * Image transformation cache
 *
 * Event listener that stores (transformed) images to disk. By using this listener Imbo will only
 * have to generate each transformation once.
 *
 * @package EventListener
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class ImageTransformationCache extends Listener implements ListenerInterface {
    /**
     * Root path where the temp. images can be stored
     *
     * @var string
     */
    private $path;

    /**
     * Content negotiation instance
     *
     * @var Imbo\Http\ContentNegotiation
     */
    private $contentNegotiation;

    /**
     * Class constructor
     *
     * @param string $path Path to store the temp. images
     * @param Imbo\Http\ContentNegotiation $contentNegotiation Content negotiation instance
     */
    public function __construct($path, ContentNegotiation $contentNegotiation = null) {
        $this->path = rtrim($path, '/');

        if ($contentNegotiation === null) {
            $contentNegotiation = new ContentNegotiation();
        }

        $this->contentNegotiation = $contentNegotiation;
    }

    /**
     * @see Imbo\EventListener\ListenerInterface::getEvents
     */
    public function getEvents() {
        return array(
            // Look for images in the cache
            'image.get.database.load.post',

            // Store images in the cache
            'image.get.post',

            // Remove from the cache when an image is deleted from Imbo
            'image.delete.post',
        );
    }

    /**
     * @see Imbo\EventListener\ListenerInterface::invoke
     */
    public function invoke(EventInterface $event) {
        $eventName = $event->getName();
        $request   = $event->getRequest();
        $response  = $event->getResponse();
        $image     = $event->getImage();

        $publicKey       = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();
        $imageExtension  = $request->getImageExtension();
        $url             = $request->getUrl();

        // Fetch the mime type of the original image
        $mimeType = $image->getMimeType();

        if ($imageExtension !== null) {
            // The user has requested a specific type (convert transformation). Use that mime type
            // instead
            $types = array_flip(Image::$mimeTypes);
            $mimeType = $types[$imageExtension];
        }

        // Generate cache key and fetch the full path of the cached response
        $hash = $this->getCacheKey($url, $mimeType);
        $fullPath = $this->getFullPath($hash);

        if ($eventName === 'image.get.database.load.post') {
            // Fetch the acceptable types from the user agent
            $acceptableTypes = array_keys($request->getAcceptableContentTypes());

            if (!$this->contentNegotiation->isAcceptable($mimeType, $acceptableTypes)) {
                // The user agent does not accept this type of image. Don't look in the cache.
                return;
            }

            if (is_file($fullPath)) {
                $response = unserialize(file_get_contents($fullPath));

                $ifNoneMatch     = $request->getHeaders()->get('if-none-match');
                $ifModifiedSince = $request->getHeaders()->get('if-modified-since');

                $etag         = $response->getHeaders()->get('etag');
                $lastModified = $response->getHeaders()->get('last-modified');

                if (
                    $ifNoneMatch && $ifModifiedSince &&
                    $lastModified === $ifModifiedSince &&
                    $etag === $ifNoneMatch
                ) {
                    $response->setNotModified();
                }

                $response->getHeaders()->set('X-Imbo-TransformationCache', 'Hit');

                $response->send();
                exit;
            }

            $response->getHeaders()->set('X-Imbo-TransformationCache', 'Miss');
        } else if ($eventName === 'image.get.post') {
            if ($response->getStatusCode() !== 200) {
                // We only want to put 200 OK responses in the cache
                return;
            }

            $dir = dirname($fullPath);

            if (is_dir($dir) || mkdir($dir, 0775, true)) {
                if (file_put_contents($fullPath . '.tmp', serialize($response))) {
                    rename($fullPath . '.tmp', $fullPath);
                }
            }
        } else if ($eventName === 'image.delete.post') {
            if (is_file($fullPath)) {
                unlink($fullPath);
            }
        }
    }

    /**
     * Get the full path based on the hash
     *
     * @param string $hash The hash used as cache key
     * @return string Returns a full path
     */
    private function getFullPath($hash) {
        return sprintf('%s/%s/%s/%s/%s', $this->path, $hash[0], $hash[1], $hash[2], $hash);
    }

    /**
     * Generate a cache key
     *
     * @param string $url The requested URL
     * @param string $mime The mime type of the image
     * @return string Returns a string that can be used as a cache key for the current image
     */
    private function getCacheKey($url, $mime) {
        return hash('sha256', $url . '|' . $mime);
    }
}
