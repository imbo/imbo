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
    Imbo\Cache\CacheInterface,
    Imbo\Model;

/**
 * Metadata cache
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Event\Listeners
 */
class MetadataCache implements ListenerInterface {
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
    public function getDefinition() {
        return array(
            // Load from cache
            new ListenerDefinition('db.metadata.load', array($this, 'loadFromCache'), 10),

            // Delete from cache
            new ListenerDefinition('db.metadata.delete', array($this, 'deleteFromCache'), -10),

            // Store in cache
            new ListenerDefinition('db.metadata.load', array($this, 'storeInCache'), -10),
            new ListenerDefinition('db.metadata.update', array($this, 'storeInCache'), -10),
        );
    }

    /**
     * Get data from the cache
     *
     * @param EventInterface $event The event instance
     */
    public function loadFromCache(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $cacheKey = $this->getCacheKey(
            $request->getPublicKey(),
            $request->getImageIdentifier()
        );

        $result = $this->cache->get($cacheKey);

        if (is_array($result) && isset($result['lastModified']) && isset($result['metadata'])) {
            $model = new Model\Metadata();
            $model->setData($result['metadata']);

            $response->setModel($model);
            $response->getHeaders()->set('X-Imbo-MetadataCache', 'Hit')
                                   ->set('Last-Modified', $result['lastModified']);

            // Stop propagation of listeners for this event
            $event->stopPropagation(true);
            return;
        }

        $response->getHeaders()->set('X-Imbo-MetadataCache', 'Miss');
    }

    /**
     * Store metadata in the cache
     *
     * @param EventInterface $event The event instance
     */
    public function storeInCache(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $cacheKey = $this->getCacheKey(
            $request->getPublicKey(),
            $request->getImageIdentifier()
        );

        // Store the response in the cache for later use
        if ($response->getStatusCode() === 200) {
            $metadata = array();

            if ($model = $response->getModel()) {
                $metadata = $model->getData();
            }

            $this->cache->set($cacheKey, array(
                'lastModified' => $response->getLastModified(),
                'metadata' => $metadata,
            ));
        }
    }

    /**
     * Delete data from the cache
     *
     * @param EventInterface $event The event instance
     */
    public function deleteFromCache(EventInterface $event) {
        $request = $event->getRequest();

        $cacheKey = $this->getCacheKey(
            $request->getPublicKey(),
            $request->getImageIdentifier()
        );

        $this->cache->delete($cacheKey);
    }

    /**
     * Generate a cache key
     *
     * @param string $publicKey The current public key
     * @param string $imageIdentifier The current image identifier
     * @return string Returns a cache key
     */
    private function getCacheKey($publicKey, $imageIdentifier) {
        return 'metadata:' . $publicKey . '|' . $imageIdentifier;
    }
}
