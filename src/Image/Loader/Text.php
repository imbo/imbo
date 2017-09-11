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
 * Text .. image .. loader. Renders text to a 300x300 texture.
 *
 * This loader is an example and should never be used in production.
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package Image\Loaders
 */
class Text implements LoaderInterface {
    public function getMimeTypeCallbacks() {
        return [
            'text/plain' => [
                'extension' => 'txt',
                'callback' => [$this, 'load'],
            ],
        ];
    }

    public function load($imagick, $blob) {
        $im = imagecreatetruecolor(300, 300);
        $textColor = imagecolorallocate($im, 0x00, 0x00, 0x00);
        $backgroundColor = imagecolorallocate($im, 0xff, 0xff, 0xff);

        imagefill($im, 0, 0, $backgroundColor);
        imagestring($im, 5, 0, 100, $blob, $textColor);

        ob_start();
        imagepng($im);
        $image_data = ob_get_contents();
        ob_end_clean();

        $imagick->readImageBlob($image_data);
        return $imagick;
    }
}