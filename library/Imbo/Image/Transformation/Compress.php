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

use Imbo\Model\Image,
    Imbo\Exception\TransformationException,
    ImagickException;

/**
 * Compression transformation
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
class Compress extends Transformation implements TransformationInterface {
    /**
     * {@inheritdoc}
     */
    public function applyToImage(Image $image, array $params = array()) {
        if (empty($params['quality'])) {
            throw new TransformationException('Missing required parameter: quality', 400);
        }

        $quality = (int) $params['quality'];

        try {
            $imagick = $this->getImagick();
            $imagick->readImageBlob($image->getBlob());
            $imagick->setImageCompressionQuality($quality);

            $image->setBlob($imagick->getImageBlob());
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }
}
