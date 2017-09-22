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
        try {
            $this->imagick->setImageAlphaChannel(\Imagick::ALPHACHANNEL_TRANSPARENT);
            $this->imagick->clipImage();
            $this->imagick->setImageAlphaChannel(\Imagick::ALPHACHANNEL_OPAQUE);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }


        $this->image->hasBeenTransformed(true);
    }
}
