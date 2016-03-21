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
 * Generate an ETag for the response
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Event\Listeners
 */
class ResponseETag implements ListenerInterface {
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'response.send' => [
                'setETag' => 5,
                'fixIfNoneMatchHeader' => 5,
            ],
        ];
    }

    /**
     * Clean up a possibly tainted If-None-Match request header
     *
     * Apache for instance can add "-gzip" to the ETag response header value, causing the matching
     * in Imbo to fail when this value comes back with a "-gzip" appended to it.
     *
     * @param EventInterface $event The current event
     */
    public function fixIfNoneMatchHeader(EventInterface $event) {
        $request = $event->getRequest();
        $ifNoneMatch = $request->headers->get('if-none-match', false);

        if ($ifNoneMatch && strlen($ifNoneMatch) > 34) {
            // Remote quotes
            $ifNoneMatch = trim($ifNoneMatch, '"');

            // Remove everything beyond char 32, that has possibly been added by web servers, and
            // set the header
            $request->headers->set('if-none-match', '"' . substr($ifNoneMatch, 0, 32) . '"');
        }
    }

    /**
     * Set the correct ETag for the response
     *
     * @param EventInterface $event The current event
     */
    public function setETag(EventInterface $event) {
        $response = $event->getResponse();
        $request = $event->getRequest();

        $routesWithETags = [
            'user' => true,
            'images' => true,
            'image' => true,
            'metadata' => true,
            'globalshorturl' => true,
        ];
        $currentRoute = (string) $request->getRoute();

        if (!isset($routesWithETags[$currentRoute])) {
            // The current route does not use ETags
            return;
        }

        if ($response->isOk()) {
            $response->setETag('"' . md5($response->getContent()) . '"');
        }
    }
}
