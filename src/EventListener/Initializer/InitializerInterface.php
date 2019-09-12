<?php
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
