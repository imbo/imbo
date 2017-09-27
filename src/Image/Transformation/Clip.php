<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Image\Transformation;

use Imbo\Exception\TransformationException,
    ImagickException;

/**
 * Clip transformation
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package Image\Transformations
 */
class Clip extends Transformation {
    /**
     * {@inheritdoc}
     */
    public function transform(array $params) {
        $pathName = null;

        if (!empty($params['name'])) {
            $pathName = $params['name'];
        }

        try {
            $this->imagick->setImageAlphaChannel(\Imagick::ALPHACHANNEL_TRANSPARENT);

            // since the implementation of clipImage in ImageMagick is
            //     return(ClipImagePath(image,"#1",MagickTrue,exception));
            // .. this should be the same by setting inside=true.
            if ($pathName) {
                $this->imagick->clipImagePath($pathName, true);
            } else {
                $this->imagick->clipImage();
            }

            $this->imagick->setImageAlphaChannel(\Imagick::ALPHACHANNEL_OPAQUE);
        } catch (ImagickException $e) {
            // NoClipPathDefined - the image doesn't have a clip path, but this isn't an fatal error.
            if ($e->getCode() == 410) {
                return;
            }

            throw new TransformationException($e->getMessage(), 400, $e);
        }


        $this->image->hasBeenTransformed(true);
    }
}
