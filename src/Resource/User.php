<?php declare(strict_types=1);

namespace Imbo\Resource;

use Imbo\EventManager\EventInterface;

class User implements ResourceInterface
{
    public function getAllowedMethods(): array
    {
        return ['GET', 'HEAD'];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'user.get' => 'get',
            'user.head' => 'get',
        ];
    }

    /**
     * Handle GET requests.
     *
     * @param EventInterface $event The current event
     */
    public function get(EventInterface $event): void
    {
        $event->getManager()->trigger('db.user.load');
    }
}
