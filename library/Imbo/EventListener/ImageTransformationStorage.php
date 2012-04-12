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
    Imbo\EventManager\EventInterface;

/**
 * Image transformation storage
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
class ImageTransformationStorage extends Listener implements ListenerInterface {
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
     * @throws Imbo\Exception\RuntimeException
     */
    public function __construct($path) {
        $this->path = rtrim($path, '/');

        // Try to create the path if it does not exist
        if ((!is_dir($path) && !@mkdir($path, 0775, true)) || !is_writable($path)) {
            throw new RuntimeException('Invalid path: ' . $path, 500);
        }
    }

    /**
     * @see Imbo\EventListener\ListenerInterface::getEvents
     */
    public function getEvents() {
        return array(
            'image.get.pre',
            'image.get.post',
        );
    }

    /**
     * @see Imbo\EventListener\ListenerInterface::invoke
     * @throws Imbo\Exception\RuntimeException
     */
    public function invoke(EventInterface $event) {
        $eventName = $event->getName();
        $request = $event->getRequest();
        $hash = md5($request->getUrl());
        $fullPath = $this->getFullPath($hash);

        if ($eventName === 'image.get.pre') {
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

                $response->send();
                exit;
            }
        } else if ($eventName === 'image.get.post') {
            $response = $event->getResponse();

            if ($response->getStatusCode() === 304) {
                // We don't want to put a 304 response in the cache
                return;
            }

            $dir = dirname($fullPath);

            if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
                throw new RuntimeException('Could not create directory: ' . $dir, 500);
            }

            file_put_contents($fullPath, serialize($response));
        }
    }

    /**
     * Get the full path based on the hash
     *
     * @param string $hash An MD5 hash (image identifier)
     * @return string Returns a full path
     */
    private function getFullPath($hash) {
        return sprintf('%s/%s/%s/%s/%s', $this->path, $hash[0], $hash[1], $hash[2], $hash);
    }
}
