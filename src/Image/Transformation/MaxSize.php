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
    Imbo\Image\InputSizeAware,
    ImagickException;

/**
 * MaxSize transformation
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
class MaxSize extends Transformation implements InputSizeAware {
    /**
     * {@inheritdoc}
     */
    public function transform(array $params) {
        $newSize = $this->calculateSize($params);

        // No need to transform? Fall back
        if (!$newSize) {
            return;
        }

        try {
            $this->imagick->thumbnailImage($newSize['width'], $newSize['height']);

            $size = $this->imagick->getImageGeometry();

            $this->image
                 ->setWidth($size['width'])
                 ->setHeight($size['height'])
                 ->hasBeenTransformed(true);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimumInputSize(array $params) {
        return $this->calculateSize($params) ?: [
            'width' => $this->image->getWidth(),
            'height' => $this->image->getHeight()
        ];
    }

    /**
     * Calculate the output size based on the specified parameters
     *
     * @param array $params
     * @return array|boolean
     */
    protected function calculateSize(array $params) {
        $image = $this->image;

        $maxWidth = !empty($params['width']) ? (int) $params['width'] : 0;
        $maxHeight = !empty($params['height']) ? (int) $params['height'] : 0;

        $sourceWidth  = $image->getWidth();
        $sourceHeight = $image->getHeight();

        $width  = $maxWidth  ?: $sourceWidth;
        $height = $maxHeight ?: $sourceHeight;

        // Figure out original ratio
        $ratio = $sourceWidth / $sourceHeight;

        if (($width / $height) > $ratio) {
            $width  = round($height * $ratio);
        } else {
            $height = round($width / $ratio);
        }

        // Is the original image smaller than the specified parameters?
        if ($sourceWidth <= $width && $sourceHeight <= $height) {
            // Original image is smaller than the max-parameters, don't transform
            return false;
        }

        return ['width' => $width, 'height' => $height];
    }
}
