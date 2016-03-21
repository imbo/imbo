<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\EventListener\Initializer;

use Imbo\EventListener\ListenerInterface;

/**
 * Event listener initializer interface
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Event\Listeners
 */
interface InitializerInterface {
    function initialize(ListenerInterface $listener);
}
