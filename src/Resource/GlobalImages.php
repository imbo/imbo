<?php
namespace Imbo\Resource;

use Imbo\EventManager\EventInterface;
use Imbo\Exception\RuntimeException;

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

        $missingAccess = [];
        $users = $event->getRequest()->getUsers() ?: $event->getDatabase()->getAllUsers();

        foreach ($users as $user) {
            $hasAccess = $acl->hasAccess(
                $event->getRequest()->getPublicKey(),
                'images.get',
                $user
            );

            if (!$hasAccess) {
                $missingAccess[] = $user;
            }
        }

        if (!empty($missingAccess)) {
            throw new RuntimeException(
                'Public key does not have access to the users: [' .
                implode(', ', $missingAccess) .
                ']',
                400
            );
        }

        $event->getManager()->trigger('db.images.load', [
            'users' => $users
        ]);
    }
}
