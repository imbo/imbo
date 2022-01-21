<?php declare(strict_types=1);
namespace Imbo\Resource;

use Imbo\EventListener\ListenerInterface;

interface ResourceInterface extends ListenerInterface
{
    /**
     * Return an array with the allowed (implemented) HTTP methods for the current resource
     */
    public function getAllowedMethods(): array;
}
