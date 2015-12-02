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
    Imbo\EventListener\ListenerInterface,
    Imbo\EventManager\EventInterface,
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
class Border extends Transformation implements ListenerInterface {
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
    public static function getSubscribedEvents() {
        return [
            'image.transformation.border' => 'transform',
        ];
    }

    /**
     * Transform the image
     *
     * @param EventInterface $event The event instance
     */
    public function transform(EventInterface $event) {
        $image = $event->getArgument('image');
        $params = $event->getArgument('params');

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
                    $this->expandImage($color, $width, $height, $image);
                } else {
                    // If we don't have an alpha channel, use the more cost-efficient `borderImage()`
                    $this->imagick->borderImage($color, $width, $height);
                }
            } else {
                // Paint the border inside of the image, keeping the orignal width/height
                $this->drawBorderInside($color, $width, $height, $image);
            }

            $size = $this->imagick->getImageGeometry();

            $image->setWidth($size['width'])
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
     * @param Image $image
     */
    private function expandImage($color, $borderWidth, $borderHeight, Image $image) {
        $imageWidth = $image->getWidth();
        $imageHeight = $image->getHeight();

        $original = clone $this->imagick;

        // Clear the original and make the canvas
        $this->imagick->clear();

        $this->imagick->newImage(
            $imageWidth  + ($borderWidth  * 2),
            $imageHeight + ($borderHeight * 2),
            $color
        );
        $this->imagick->setImageAlphaChannel(Imagick::ALPHACHANNEL_OPAQUE);
        $this->imagick->setImageFormat($image->getExtension());

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
     * @param Image $image
     */
    private function drawBorderInside($color, $borderWidth, $borderHeight, Image $image) {
        $imageWidth = $image->getWidth();
        $imageHeight = $image->getHeight();

        $rect = new ImagickDraw();
        $rect->setStrokeColor($color);
        $rect->setFillColor($color);
        $rect->setStrokeAntialias(false);

        // Left
        $rect->rectangle(0, 0, $borderWidth - 1, $imageHeight);

        // Right
        $rect->rectangle($imageWidth - $borderWidth, 0, $imageWidth, $imageHeight);

        // Top
        $rect->rectangle(0, 0, $imageWidth, $borderHeight - 1);

        // Bottom
        $rect->rectangle(0, $imageHeight - $borderHeight, $imageWidth, $imageHeight);

        // Draw the border
        $this->imagick->drawImage($rect);
    }
}
