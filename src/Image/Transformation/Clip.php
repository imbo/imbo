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

use Imbo\Exception\InvalidArgumentException,
    Imbo\Exception\TransformationException,
    ImagickException;

/**
 * Clip transformation for making an image transparent outside of a clipping mask
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

            $metadata = $this->event->getDatabase()->getMetadata(
                $this->image->getUser(),
                $this->image->getImageIdentifier()
            );

            if (empty($metadata['paths']) || !is_array($metadata['paths']) || !in_array($pathName, $metadata['paths'])) {
                if (isset($params['ignoreUnknownPath'])) {
                    return;
                }

                throw new InvalidArgumentException('Selected clipping path "' . $pathName . '" was not found in the image. Add the ignoreUnknownPath argument if you want to ignore this error.', 400);
            }
        }

        $currentAlphaChannelMode = $this->imagick->getImageAlphaChannel();

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
            // NoClipPathDefined - the image doesn't have a clipping path, but this isn't a fatal error.
            if ($e->getCode() == 410) {
                // but we need to reset the alpha channel mode in case someone else is doing something with it
                $this->imagick->setImageAlphaChannel($currentAlphaChannelMode);
                return;
            }

            throw new TransformationException($e->getMessage(), 400, $e);
        }


        $this->image->hasBeenTransformed(true);
    }
}
