<?php declare(strict_types=1);
namespace Imbo\Resource;

use Imbo\EventManager\EventInterface;
use Imbo\Exception\RuntimeException;
use Imbo\Http\Response\Response;

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
class GlobalImages implements ResourceInterface
{
    public function getAllowedMethods(): array
    {
        return ['GET', 'HEAD'];
    }

    public static function getSubscribedEvents(): array
    {
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
    public function getImages(EventInterface $event): void
    {
        $acl = $event->getAccessControl();

        $missingAccess = [];
        $users = $event->getRequest()->getUsers() ?: $event->getDatabase()->getAllUsers();

        foreach ($users as $user) {
            $hasAccess = $acl->hasAccess(
                $event->getRequest()->getPublicKey(),
                'images.get',
                $user,
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
                Response::HTTP_BAD_REQUEST,
            );
        }

        $event->getManager()->trigger('db.images.load', [
            'users' => $users,
        ]);
    }
}
