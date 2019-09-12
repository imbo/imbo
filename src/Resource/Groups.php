<?php
namespace Imbo\Resource;

use Imbo\EventManager\EventInterface;

class Groups implements ResourceInterface {
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
            'groups.get' => 'listGroups',
            'groups.head' => 'listGroups',
        ];
    }

    /**
     * Get a list of available resource groups
     *
     * @param EventInterface $event The current event
     */
    public function listGroups(EventInterface $event) {
        $event->getManager()->trigger('acl.groups.load');
    }
}
