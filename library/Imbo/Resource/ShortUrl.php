<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Resource;

use Imbo\EventManager\EventInterface,
    Imbo\EventListener\ListenerDefinition,
    Imbo\Exception\ResourceException,
    Symfony\Component\HttpFoundation\ParameterBag;


/**
 * Short URL resource
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Resources
 */
class ShortUrl implements ResourceInterface {
    /**
     * {@inheritdoc}
     */
    public function getAllowedMethods() {
        return array('GET', 'HEAD');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return array(
            // Generate and/or fetch short URL
            'shorturl.get' => 'get',
            'shorturl.head' => 'get',

            // Add a short URL header to the response
            'image.get' => 'addShortUrlHeader',
            'image.head' => 'addShortUrlHeader',

            // Remove short URLs
            'image.delete' => 'deleteShortUrls',
        );
    }

    /**
     * Add a short URL header to the current image request (unless the request was originally a
     * shorturl request, in which case the response already has a short URL header)
     *
     * @param EventInterface $event
     */
    public function addShortUrlHeader(EventInterface $event) {
        $response = $event->getResponse();

        if ($response->headers->has('X-Imbo-ShortUrl')) {
            return;
        }

        $database = $event->getDatabase();
        $request = $event->getRequest();

        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();
        $extension = $request->getExtension();
        $query = $request->query->all();

        $shortUrlId = $database->getShortUrlId($publicKey, $imageIdentifier, $extension, $query);

        if (!$shortUrlId) {
            do {
                // No short URL exists, generate an ID and insert
                $shortUrlId = $this->getShortUrlId();
            } while($database->getShortUrlParams($shortUrlId));

            $database->insertShortUrl($shortUrlId, $publicKey, $imageIdentifier, $extension, $query);
        }

        // Attach the header
        $response->headers->set('X-Imbo-ShortUrl', $request->getSchemeAndHttpHost(). '/s/' . $shortUrlId);
    }

    /**
     * Delete short URLs registered to the image that was just deleted
     *
     * @param EventInterface $event
     */
    public function deleteShortUrls(EventInterface $event) {
        $request = $event->getRequest();
        $event->getDatabase()->deleteShortUrls(
            $request->getPublicKey(),
            $request->getImageIdentifier()
        );
    }

    /**
     * Fetch an image via a short URL
     *
     * @param EventInterface $event
     */
    public function get(EventInterface $event) {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $route = $request->getRoute();

        $params = $event->getDatabase()->getShortUrlParams($route->get('shortUrlId'));

        if (!$params) {
            throw new ResourceException('Image not found', 404);
        }

        $route->set('publicKey', $params['publicKey']);
        $route->set('imageIdentifier', $params['imageIdentifier']);
        $route->set('extension', $params['extension']);

        $request->query = new ParameterBag($params['query']);
        $response->headers->set('X-Imbo-ShortUrl', $request->getUri());

        $event->getManager()->trigger('image.get');
    }

    /**
     * Method for generating short URL keys
     *
     * @return string
     */
    private function getShortUrlId($len = 7) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $charsLen = 62;
        $key = '';

        for ($i = 0; $i < $len; $i++) {
            $key .= $chars[mt_rand() % $charsLen];
        }

        return $key;
    }
}
