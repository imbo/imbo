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
use Imbo\Image\Loader\LoaderInterface;

/**
 * Simple image loader / fallback image loader
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package Image\Loaders
 */
class Simple implements LoaderInterface {
    public function getMimeTypeCallbacks() {
        return [
            'image/png' => [$this, 'load'],
            'image/jpeg' => [$this, 'load'],
            'image/gif' => [$this, 'load'],
        ];
    }

    public function load($blob) {
        return new Imagick($blob);
    }
}