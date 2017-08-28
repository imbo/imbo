<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Image\Loader;

/**
 * Loader interface
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package Loaders
 */
interface LoaderInterface {
    public function getMimeTypeCallbacks();
}
