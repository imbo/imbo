<?php declare(strict_types=1);

namespace Imbo\EventListener\Initializer;

use Imbo\EventListener\ListenerInterface;

/**
 * Event listener initializer interface.
 */
interface InitializerInterface
{
    public function initialize(ListenerInterface $listener): void;
}
