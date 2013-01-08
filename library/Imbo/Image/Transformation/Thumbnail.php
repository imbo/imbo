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

use Imbo\Image\Image,
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
     * Class constructor
     *
     * @param array $params Parameters for this transformation
     */
    public function __construct(array $params = array()) {
        if (!empty($params['width'])) {
            $this->width = (int) $params['width'];
        }

        if (!empty($params['height'])) {
            $this->height = (int) $params['height'];
        }

        if (!empty($params['fit'])) {
            $this->fit = $params['fit'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function applyToImage(Image $image) {
        try {
            $imagick = $this->getImagick();
            $imagick->setOption('jpeg:size', $this->width . 'x' . $this->height);
            $imagick->readImageBlob($image->getBlob());

            if ($this->fit == 'inset') {
                $imagick->thumbnailimage($this->width, $this->height, true);
            } else {
                $imagick->cropThumbnailImage($this->width, $this->height);
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
