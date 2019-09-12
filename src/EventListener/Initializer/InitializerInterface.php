<?php
namespace Imbo\EventListener\Initializer;

use Imbo\EventListener\ListenerInterface;

/**
 * Event listener initializer interface
 */
interface InitializerInterface {
    function initialize(ListenerInterface $listener);
}
