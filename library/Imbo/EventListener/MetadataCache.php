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
    Imbo\Cache\CacheInterface,
    Imbo\Http\Response\ResponseInterface;

/**
 * Metadata cache
 *
 * @package EventListener
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class MetadataCache extends Listener implements ListenerInterface {
    /**
     * Cache driver
     *
     * @var CacheInterface
     */
    private $cache;

    /**
     * Class constructor
     *
     * @param CacheInterface $cache Cache implementation
     */
    public function __construct(CacheInterface $cache) {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function getEvents() {
        return array(
            // Look for metadata in the cache
            'metadata.get.pre',

            // Store metadata in the cache
            'metadata.get.post',

            // Remove metadata from the cache
            'metadata.delete.pre',
            'metadata.put.post',
            'metadata.post.post',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function invoke(EventInterface $event) {
        $eventName = $event->getName();
        $container = $event->getContainer();

        $request = $container->request;
        $response = $container->response;

        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();

        // Get cache key
        $cacheKey = $this->getCacheKey($publicKey, $imageIdentifier);

        if ($eventName === 'metadata.get.pre') {
            $result = $this->cache->get($cacheKey);

            if ($result instanceof ResponseInterface) {
                $result->getHeaders()->set('X-Imbo-MetadataCache', 'Hit');

                // We have a valid response object from the cache. Overwrite the one already in the
                // container
                $container->response = $result;

                // Halt execution of the application and return
                $event->haltExecution(true);
                return;
            }

            $response->getHeaders()->set('X-Imbo-MetadataCache', 'Miss');
        } else if ($eventName === 'metadata.get.post') {
            // Store the response in the cache for later use
            $this->cache->set($cacheKey, $response);
        } else {
            // Remove metadata from the cache
            $this->cache->delete($cacheKey);
        }
    }

    /**
     * Generate a cache key
     *
     * @param string $url The requested URL
     * @param string $mime The mime type of the image
     * @return string Returns a string that can be used as a cache key for the current image
     */
    private function getCacheKey($publicKey, $imageIdentifier) {
        return 'metadata:' . $publicKey . '|' . $imageIdentifier;
    }
}
