<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Model;

/**
 * Model interface
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Models
 */
interface ModelInterface {
    /**
     * Return the "data" found in the model
     *
     * @return mixed
     */
    function getData();
}
