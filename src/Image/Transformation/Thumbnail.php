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
 * Thumbnail transformation
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
class Thumbnail extends Transformation implements InputSizeAware {
    /**
     * Width of the thumbnail
     *
     * @var int
     */
    private $width = 50;

    /**
     * Height of the thumbnail
     *
     * @var int
     */
    private $height = 50;

    /**
     * Fit type
     *
     * The thumbnail fit style. 'inset' or 'outbound'
     *
     * @var string
     */
    private $fit = 'outbound';

    /**
     * {@inheritdoc}
     */
    public function transform(array $params) {
        $width = !empty($params['width']) ? (int) $params['width'] : $this->width;
        $height = !empty($params['height']) ? (int) $params['height'] : $this->height;
        $fit = !empty($params['fit']) ? $params['fit'] : $this->fit;

        try {
            if ($fit === 'inset') {
                $this->imagick->thumbnailImage($width, $height, true);
            } else {
                $this->imagick->cropThumbnailImage($width, $height);
            }

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
        $fit = isset($params['fit']) ? $params['fit'] : $this->fit;
        $width = !empty($params['width']) ? (int) $params['width'] : $this->width;
        $height = !empty($params['height']) ? (int) $params['height'] : $this->height;
        $ratio = $this->image->getWidth() / $this->image->getHeight();

        if ($fit !== 'inset') {
            return ['width' => $width, 'height' => $height];
        }

        $sourceWidth = $this->image->getWidth();
        $sourceHeight = $this->image->getHeight();

        $ratioX = $width  / $sourceWidth;
        $ratioY = $height / $sourceHeight;

        if ($ratioX === $ratioY) {
            return ['width' => $width, 'height' => $height];
        } else if ($ratioX < $ratioY) {
            return ['width' => $width, 'height' => (int) max(1, $ratioX * $sourceHeight)];
        }

        return ['width' => (int) max(1, $ratioY * $sourceWidth), 'height' => $height];
    }
}
