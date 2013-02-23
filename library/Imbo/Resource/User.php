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
    Imbo\EventListener\ListenerDefinition,
    Imbo\EventListener\ListenerInterface;

/**
 * User resource
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Resources
 */
class User implements ResourceInterface, ListenerInterface {
    /**
     * {@inheritdoc}
     */
    public function getAllowedMethods() {
        return array('GET', 'HEAD');
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition() {
        return array(
            new ListenerDefinition('user.get', array($this, 'get')),
            new ListenerDefinition('user.head', array($this, 'get')),
        );
    }

    /**
     * Handle GET requests
     *
     * @param EventInterface $event The current event
     */
    public function get(EventInterface $event) {
        $event->getManager()->trigger('db.user.load');

        $response = $event->getResponse();
        $response->setEtag('"' . md5($response->getLastModified()->format('D, d M Y H:i:s') . ' GMT') . '"');
    }
}
