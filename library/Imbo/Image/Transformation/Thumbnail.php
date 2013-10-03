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
 * Thumbnail transformation
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
class Thumbnail extends Transformation implements TransformationInterface {
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
    public function applyToImage(Image $image, array $params = array()) {
        $width = !empty($params['width']) ? (int) $params['width'] : $this->width;
        $height = !empty($params['height']) ? (int) $params['height'] : $this->height;
        $fit = !empty($params['fit']) ? $params['fit'] : $this->fit;

        try {
            $imagick = $this->getImagick();
            $imagick->setOption('jpeg:size', $width . 'x' . $height);
            $imagick->readImageBlob($image->getBlob());

            if ($fit === 'inset') {
                $imagick->thumbnailimage($width, $height, true);
            } else {
                $imagick->cropThumbnailImage($width, $height);
            }

            $size = $imagick->getImageGeometry();

            $image->setBlob($imagick->getImageBlob())
                  ->setWidth($size['width'])
                  ->setHeight($size['height']);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }
}
