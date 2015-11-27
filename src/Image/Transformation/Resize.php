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
    Imbo\Image\InputSizeConstraint,
    ImagickException;

/**
 * Resize transformation
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
class Resize extends Transformation implements InputSizeConstraint {
    /**
     * {@inheritdoc}
     */
    public function transform(array $params) {
        $size = $this->calculateSize($params);

        // Fall back if there is no need to resize
        if (!$size) {
            return;
        }

        try {
            $this->imagick->thumbnailImage($size['width'], $size['height']);

            $newSize = $this->imagick->getImageGeometry();

            $this->image
                 ->setWidth($newSize['width'])
                 ->setHeight($newSize['height'])
                 ->hasBeenTransformed(true);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimumInputSize(array $params) {
        return $this->calculateSize($params);
    }

    /**
     * Calculate output size of image
     *
     * @param array $params
     * @return array
     */
    protected function calculateSize(array $params) {
        if (empty($params['width']) && empty($params['height'])) {
            throw new TransformationException(
                'Missing both width and height. You need to specify at least one of them',
                400
            );
        }

        $width = !empty($params['width']) ? (int) $params['width'] : 0;
        $height = !empty($params['height']) ? (int) $params['height'] : 0;

        $originalWidth = $this->image->getWidth();
        $originalHeight = $this->image->getHeight();

        if ($width === $originalWidth && $height === $originalHeight) {
            // Resize params match the current image size, no need for any resizing
            return false;
        }

        // Calculate width or height if not both have been specified
        if (!$height) {
            $height = ceil(($originalHeight / $originalWidth) * $width);
        } else if (!$width) {
            $width = ceil(($originalWidth / $originalHeight) * $height);
        }

        return ['width' => $width, 'height' => $height];
    }
}
