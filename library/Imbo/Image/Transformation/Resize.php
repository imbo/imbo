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
 * Resize transformation
 *
 * @package Image
 * @subpackage Transformation
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class Resize extends Transformation implements TransformationInterface {
    /**
     * Width of the resize
     *
     * @var int
     */
    private $width;

    /**
     * Height of the resize
     *
     * @var int
     */
    private $height;

    /**
     * Class constructor
     *
     * @param array $params Parameters for this transformation
     * @throws TransformationException
     */
    public function __construct(array $params) {
        if (empty($params['width']) && empty($params['height'])) {
            throw new TransformationException('Missing both width and height. You need to specify at least one of them', 400);
        }

        $this->width = !empty($params['width']) ? (int) $params['width'] : 0;
        $this->height = !empty($params['height']) ? (int) $params['height'] : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToImage(Image $image) {
        try {
            $width  = $this->width  ?: null;
            $height = $this->height ?: null;

            // Calculate width or height if not both have been specified
            if (!$height) {
                $height = ($image->getHeight() / $image->getWidth()) * $width;
            } else if (!$width) {
                $width = ($image->getWidth() / $image->getHeight()) * $height;
            }

            $imagick = $this->getImagick();
            $imagick->setOption('jpeg:size', $width . 'x' . $height);
            $imagick->readImageBlob($image->getBlob());
            $imagick->thumbnailImage($width, $height);

            $size = $imagick->getImageGeometry();

            $image->setBlob($imagick->getImageBlob())
                  ->setWidth($size['width'])
                  ->setHeight($size['height']);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }
}
