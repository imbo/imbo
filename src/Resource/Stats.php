<?php
namespace Imbo\Resource;

use Imbo\EventManager\EventInterface;

/**
 * Stats resource
 *
 * This resource can be used to monitor the imbo installation to see if it has access to the
 * current database and storage.
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Resources
 */
class Stats implements ResourceInterface {
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
            'stats.get' => 'get',
            'stats.head' => 'get',
        ];
    }

    /**
     * Handle GET requests
     *
     * @param EventInterface $event The current event
     */
    public function get(EventInterface $event) {
        $response = $event->getResponse();
        $response->setMaxAge(0)
                 ->setPrivate();

        $response->headers->addCacheControlDirective('no-store');

        $event->getManager()->trigger('db.stats.load');
    }
}
