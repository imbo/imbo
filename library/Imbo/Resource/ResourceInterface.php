<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Resource;

use Imbo\EventListener\ListenerInterface;

/**
 * Resource interface
 *
 * Available resources must implement this interface. They can also extend the abstract resource
 * class (Imbo\Resource\Resource) for convenience.
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
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
