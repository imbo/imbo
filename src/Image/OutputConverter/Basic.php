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

use Imbo\Exception\OutputConverterException;
use Imbo\Model\Image;
use Imagick;
use ImagickException;

/**
 * Basic output converter that supports gif/png/jpg.
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package Image\OutputConverters
 */
class Basic implements OutputConverterInterface {
    /**
     * {@inheritdoc}
     */
    public function getSupportedMimeTypes() {
        return [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => 'png',
            'image/gif' => 'gif',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function convert(Imagick $imagick, Image $image, $extension, $mimeType) {
        try {
            $imagick->setImageFormat($extension);

            // Levels from 0 - 100 will work for both JPEG and PNG, although the level has different
            // meaning for these two image types. For PNG's a high level will mean more compression,
            // which usually results in a smaller file size, as for JPEG's, a high level means a
            // higher quality, resulting in a larger file size.
            if ($image->getOutputQualityCompression() !== null && ($mimeType !== 'image/gif')) {
                $imagick->setImageCompressionQuality($image->getOutputQualityCompression());
            }
        } catch (ImagickException $e) {
            throw new OutputConverterException($e->getMessage(), 400, $e);
        }

        $image->hasBeenTransformed(true);
    }
}
