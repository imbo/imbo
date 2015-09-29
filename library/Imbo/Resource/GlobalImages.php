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
    Imbo\Model;

/**
 * Global images resource
 *
 * This resource will let users fetch images based on queries. The following query parameters can
 * be used:
 *
 * page     => Page number. Defaults to 1
 * limit    => Limit to a number of images pr. page. Defaults to 20
 * metadata => Whether or not to include metadata pr. image. Set to 1 to enable
 * from     => Unix timestamp to fetch from
 * to       => Unit timestamp to fetch to
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Resources
 */
class GlobalImages implements ResourceInterface {
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
            'globalimages.get'    => 'getImages',
            'globalimages.head'   => 'getImages',
        ];
    }

    /**
     * Handle GET and HEAD requests
     *
     * @param EventInterface $event The current event
     */
    public function getImages(EventInterface $event) {
        $acl = $event->getAccessControl();

        // Get intersection between specified users and users which
        // the public key has access to the given endpoint for
        $users = array_intersect(
            $event->getRequest()->getUsers(),
            $acl->getUsersForResource(
                $event->getRequest()->getPublicKey(),
                'images.get'
            )
        );

        $event->getManager()->trigger('db.images.load', [
            'users' => $users
        ]);
    }
}
