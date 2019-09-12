<?php
namespace Imbo\Resource;

use Imbo\EventListener\ListenerInterface;

/**
 * Resource interface
 *
 * Available resources must implement this interface. They can also extend the abstract resource
 * class (Imbo\Resource\Resource) for convenience.
 *
 * @package Resources
 */
interface ResourceInterface extends ListenerInterface {
    /**
     * Return an array with the allowed (implemented) HTTP methods for the current resource
     *
     * @return string[]
     */
    function getAllowedMethods();
}
