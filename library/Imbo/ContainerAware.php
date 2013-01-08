<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo;

/**
 * Container aware interface
 *
 * @package Interfaces
 * @subpackage Exceptions
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
interface ContainerAware {
    /**
     * Set an instance of the container
     *
     * @param Container $container A populated container
     */
    function setContainer(Container $container);
}
