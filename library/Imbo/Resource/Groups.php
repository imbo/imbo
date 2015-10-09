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

use Imbo\EventManager\EventInterface;

/**
 * Resource groups resource
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Resources
 */
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
