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
    Imbo\Exception\ResourceException,
    Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Global short URL resource
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Resources
 */
class GlobalShortUrl implements ResourceInterface {
    /**
     * {@inheritdoc}
     */
    public function getAllowedMethods() {
        return ['GET', 'HEAD'];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            // Fetch an image using the short URL
            'globalshorturl.get' => 'getImage',
            'globalshorturl.head' => 'getImage',
        ];
    }

    /**
     * Fetch an image via a short URL
     *
     * @param EventInterface $event
     */
    public function getImage(EventInterface $event) {
        $request = $event->getRequest();
        $route = $request->getRoute();

        $params = $event->getDatabase()->getShortUrlParams($route->get('shortUrlId'));

        if (!$params) {
            throw new ResourceException('Image not found', 404);
        }

        $route->set('user', $params['user']);
        $route->set('imageIdentifier', $params['imageIdentifier']);
        $route->set('extension', $params['extension']);

        $request->query = new ParameterBag($params['query']);
        $event->getResponse()->headers->set('X-Imbo-ShortUrl', $request->getUri());
        $event->getManager()->trigger('image.get', ['skipAccessControl' => true]);
    }
}
