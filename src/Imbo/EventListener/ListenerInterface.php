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
     * Single callbacks can use the simplest method, defaulting to a priority of 0
     *
     * return [
     *     'event' => 'someMethod',
     *     'event2' => 'someOtherMethod',
     * ];
     *
     * If you want to specify multiple callbacks and/or a priority for the callback(s):
     *
     * return [
     *     'event' => [
     *         'someMethod', // Defaults to priority 0, same as 'someMethod' => 0
     *         'someOtherMethod' => 10, // Will trigger before "someMethod"
     *         'someThirdMethod' => -10, // Will trigger after "someMethod"
     *     ],
     *     'event2' => 'someOtherMethod',
     * ];
     *
     * @return array
     */
    static function getSubscribedEvents();
}
