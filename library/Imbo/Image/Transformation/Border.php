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
    Imbo\EventListener\ListenerInterface,
    Imbo\EventManager\EventInterface,
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
        return array(
            'image.transformation.border' => 'transform',
        );
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
        $width = !empty($params['width']) ? (int) $params['width'] : $this->width;
        $height = !empty($params['height']) ? (int) $params['height'] : $this->height;
        $mode = !empty($params['mode']) ? $params['mode'] : $this->mode;

        try {
            if ($mode === 'outbound') {
                // Paint the border outside of the image, increasing the width/height
                $this->imagick->borderImage($color, $width, $height);
            } else {
                // Paint the border inside of the image, keeping the orignal width/height
                $imageWidth = $image->getWidth();
                $imageHeight = $image->getHeight();

                $rect = new ImagickDraw();
                $rect->setStrokeColor($color);
                $rect->setFillColor($color);
                $rect->setStrokeAntialias(false);

                // Left
                $rect->rectangle(0, 0, $width - 1, $imageHeight);

                // Right
                $rect->rectangle($imageWidth - $width, 0, $imageWidth, $imageHeight);

                // Top
                $rect->rectangle(0, 0, $imageWidth, $height - 1);

                // Bottom
                $rect->rectangle(0, $imageHeight - $height, $imageWidth, $imageHeight);

                // Draw the border
                $this->imagick->drawImage($rect);
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
}
