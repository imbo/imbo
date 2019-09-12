<?php
namespace Imbo\EventListener\Initializer;

use Imbo\EventListener\ListenerInterface;

/**
 * Event listener initializer interface
 *
 * @package Event\Listeners
 */
interface InitializerInterface {
    function initialize(ListenerInterface $listener);
}
