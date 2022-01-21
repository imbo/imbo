<?php declare(strict_types=1);
namespace Imbo\Resource;

use Imbo\EventManager\EventInterface;

/**
 * Stats resource
 *
 * This resource can be used to monitor the imbo installation to see if it has access to the
 * current database and storage.
 */
class Stats implements ResourceInterface
{
    public function getAllowedMethods(): array
    {
        return ['GET', 'HEAD'];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'stats.get' => 'get',
            'stats.head' => 'get',
        ];
    }

    /**
     * Handle GET requests
     *
     * @param EventInterface $event The current event
     */
    public function get(EventInterface $event): void
    {
        $response = $event->getResponse();
        $response->setMaxAge(0)
                 ->setPrivate();

        $response->headers->addCacheControlDirective('no-store');

        $event->getManager()->trigger('db.stats.load');
    }
}
