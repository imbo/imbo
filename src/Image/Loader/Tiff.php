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
 * TIFF image loader
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package Image\Loaders
 */
class Tiff implements LoaderInterface {
    public function getMimeTypeCallbacks() {
        return [
            'image/tiff' => [
                'extension' => 'tif',
                'callback' => [$this, 'load'],
            ],
        ];
    }

    public function load($imagick, $blob) {
        $imagick->readImageBlob($blob);
        return $imagick;
    }
}