<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Image\OutputConverter;

/**
 * Output converter for outputting Webp
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package Image\OutputConverters
 */
class Webp implements OutputConverterInterface {
    public function getSupportedFormatsWithCallbacks() {
        return [
            [
                'mime' => 'image/webp',
                'extension' => ['webp'],
                'callback' => [$this, 'convert'],
            ],
        ];
    }

    public function convert($imagick, $image, $extension, $mime = null) {
        try {
            $imagick->setImageFormat($extension);
        } catch (ImagickException $e) {
            throw new OutputConversionException($e->getMessage(), 400, $e);
        }

        $image->hasBeenTransformed(true);
    }
}
