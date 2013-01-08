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
     * Return a list of listener definitions
     *
     * @return ListenerDefinition[]
     */
    function getDefinition();
}
