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

use Imbo\EventManager\EventInterface;

/**
 * Http cache listener
 *
 * This event listener will listen to all outgoing responses and check if any HTTP cache headers
 * have explicitly been set. If not, it will apply the configured defaults.
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Event\Listeners
 */
class HttpCache implements ListenerInterface {
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'response.send' => ['setHeaders' => 1],
        ];
    }

    /**
     * Right before the response is sent to the client, check if any HTTP cache control headers
     * have explicity been set for this response. If not, apply the configured defaults.
     *
     * @param EventInterface $event The event instance
     */
    public function setHeaders(EventInterface $event) {
        $method = $event->getRequest()->getMethod();

        // Obviously we shouldn't bother doing any HTTP caching logic for non-GET/HEAD requests
        if ($method !== 'GET' && $method !== 'HEAD') {
            return;
        }

        $response = $event->getResponse();
        $headers = $event->getResponse()->headers;

        // Imbo defaults to 'public' as cache-control value - if it has changed from this value,
        // assume the resource requested has explicitly defined its own caching rules and fall back
        if ($headers->get('Cache-Control') !== 'public') {
            return;
        }

        // Get configured HTTP cache defaults from configuration, then apply them
        $config = $event->getConfig()['httpCacheHeaders'];

        if (isset($config['maxAge'])) {
            $response->setMaxAge((int) $config['maxAge']);
        }

        if (isset($config['sharedMaxAge'])) {
            $response->setSharedMaxAge($config['sharedMaxAge']);
        }

        if (isset($config['public']) && $config['public']) {
            $response->setPublic();
        } else if (isset($config['public'])) {
            $response->setPrivate();
        }

        if (isset($config['mustRevalidate']) && $config['mustRevalidate']) {
            $headers->addCacheControlDirective('must-revalidate');
        }
    }
}
