<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\EventListener;

/**
 * Event listener interface
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Event\Listeners
 */
interface ListenerInterface {
    /**
     * Return an array with events to subscribe to
     *
     * No proirity (priority value defaults to 0):
     *
     * return array(
     *     'event' => 'someMethod',
     * );
     *
     * Priority:
     *
     * return array(
     *     'event' => array('someMethod', 123),
     * );
     *
     * @return array
     */
    static function getSubscribedEvents();
}
