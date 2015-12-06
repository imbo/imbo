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
    Imbo\Model\Image,
    Imagick,
    ImagickPixel,
    ImagickException,
    ImagickPixelException,
    ImagickDraw;

/**
 * Border transformation
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
class Border extends Transformation implements InputSizeConstraint {
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
     * {@inheritdoc}
     */
    public function transform(array $params) {
        $color = !empty($params['color']) ? $this->formatColor($params['color']) : $this->color;
        $width = isset($params['width']) ? (int) $params['width'] : $this->width;
        $height = isset($params['height']) ? (int) $params['height'] : $this->height;
        $mode = !empty($params['mode']) ? $params['mode'] : $this->mode;

        try {
            if ($mode === 'outbound') {
                // Paint the border outside of the image, increasing the width/height
                if ($this->imagick->getImageAlphaChannel() !== 0) {
                    // If we have an alpha channel and call `borderImage()`, Imagick will remove
                    // the alpha channel - if we have an alpha channel, use an alternative approach
                    $this->expandImage($color, $width, $height);
                } else {
                    // If we don't have an alpha channel, use the more cost-efficient `borderImage()`
                    $this->imagick->borderImage($color, $width, $height);
                }
            } else {
                // Paint the border inside of the image, keeping the orignal width/height
                $this->drawBorderInside($color, $width, $height);
            }

            $size = $this->imagick->getImageGeometry();

            $this->image->setWidth($size['width'])
                  ->setHeight($size['height'])
                  ->hasBeenTransformed(true);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        } catch (ImagickPixelException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }

    /**
     * Expand the image so that we can fit the width and height of the borders specified on each
     * side, than copy the original image to the center of the canvas.
     *
     * @param string $color
     * @param integer $borderWidth
     * @param integer $borderHeight
     */
    private function expandImage($color, $borderWidth, $borderHeight) {
        $this->imageWidth = $this->image->getWidth();
        $this->imageHeight = $this->image->getHeight();

        $original = clone $this->imagick;

        // Clear the original and make the canvas
        $this->imagick->clear();

        $this->imagick->newImage(
            $this->imageWidth  + ($borderWidth  * 2),
            $this->imageHeight + ($borderHeight * 2),
            $color
        );
        $this->imagick->setImageAlphaChannel(Imagick::ALPHACHANNEL_OPAQUE);
        $this->imagick->setImageFormat($this->image->getExtension());

        $this->imagick->compositeImage(
            $original,
            Imagick::COMPOSITE_COPY,
            $borderWidth,
            $borderHeight
        );
    }

    /**
     * Draw border inside (on top of) the existing image
     *
     * @param string $color
     * @param integer $borderWidth
     * @param integer $borderHeight
     */
    private function drawBorderInside($color, $borderWidth, $borderHeight) {
        $this->imageWidth = $this->image->getWidth();
        $this->imageHeight = $this->image->getHeight();

        $rect = new ImagickDraw();
        $rect->setStrokeColor($color);
        $rect->setFillColor($color);
        $rect->setStrokeAntialias(false);

        // Left
        $rect->rectangle(0, 0, $borderWidth - 1, $this->imageHeight);

        // Right
        $rect->rectangle($this->imageWidth - $borderWidth, 0, $this->imageWidth, $this->imageHeight);

        // Top
        $rect->rectangle(0, 0, $this->imageWidth, $borderHeight - 1);

        // Bottom
        $rect->rectangle(0, $this->imageHeight - $borderHeight, $this->imageWidth, $this->imageHeight);

        // Draw the border
        $this->imagick->drawImage($rect);
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimumInputSize(array $params, array $imageSize) {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function adjustParameters($ratio, array $parameters) {
        foreach (['width', 'height'] as $param) {
            if (isset($parameters[$param])) {
                $parameters[$param] = round($parameters[$param] / $ratio);
            }
        }

        return $parameters;
    }
}
