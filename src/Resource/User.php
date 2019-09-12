<?php
namespace Imbo\Resource;

use Imbo\EventManager\EventInterface;

/**
 * User resource
 *
 * @package Resources
 */
class User implements ResourceInterface {
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
            'user.get' => 'get',
            'user.head' => 'get',
        ];
    }

    /**
     * Handle GET requests
     *
     * @param EventInterface $event The current event
     */
    public function get(EventInterface $event) {
        $event->getManager()->trigger('db.user.load');
    }
}
