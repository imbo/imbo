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
    ImagickException,
    ImagickPixelException,
    ImagickDraw;

/**
 * Border transformation
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
class Border extends Transformation implements TransformationInterface {
    /**
     * Color of the border
     *
     * @var string
     */
    private $color = '#000';

    /**
     * Width of the border
     *
     * @var int
     */
    private $width = 1;

    /**
     * Height of the border
     *
     * @var int
     */
    private $height = 1;

    /**
     * Border mode, "inline" or "outbound"
     *
     * @var string
     */
    private $mode = 'outbound';

    /**
     * Class constructor
     *
     * @param array $params Parameters for this transformation
     */
    public function __construct(array $params = array()) {
        if (!empty($params['color'])) {
            $this->color = $this->formatColor($params['color']);
        }

        if (!empty($params['width'])) {
            $this->width = (int) $params['width'];
        }

        if (!empty($params['height'])) {
            $this->height = (int) $params['height'];
        }

        if (!empty($params['mode'])) {
            $this->mode = $params['mode'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function applyToImage(Image $image) {
        try {
            $imagick = $this->getImagick();
            $imagick->readImageBlob($image->getBlob());

            if ($this->mode === 'outbound') {
                // Paint the border outside of the image, increasing the width/height
                $imagick->borderImage($this->color, $this->width, $this->height);
            } else {
                // Paint the border inside of the image, keeping the orignal width/height
                $imageWidth = $image->getWidth();
                $imageHeight = $image->getHeight();

                $rect = new ImagickDraw();
                $rect->setStrokeColor($this->color);
                $rect->setFillColor($this->color);
                $rect->setStrokeAntialias(false);

                // Left
                $rect->rectangle(0, 0, $this->width - 1, $imageHeight);

                // Right
                $rect->rectangle($imageWidth - $this->width, 0, $imageWidth, $imageHeight);

                // Top
                $rect->rectangle(0, 0, $imageWidth, $this->height - 1);

                // Bottom
                $rect->rectangle(0, $imageHeight - $this->height, $imageWidth, $imageHeight);

                // Draw the border
                $imagick->drawImage($rect);
            }

            $size = $imagick->getImageGeometry();

            $image->setBlob($imagick->getImageBlob())
                  ->setWidth($size['width'])
                  ->setHeight($size['height']);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        } catch (ImagickPixelException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }
}
