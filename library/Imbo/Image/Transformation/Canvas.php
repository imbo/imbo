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
    Imagick,
    ImagickException,
    ImagickPixelException;

/**
 * Canvas transformation
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
class Canvas extends Transformation implements ListenerInterface {
    /**
     * Canvas mode
     *
     * Supported modes:
     *
     * - "free" (default): Uses both x and y properties for placement
     * - "center": Places the existing image in the center of the x and y axis
     * - "center-x": Places the existing image in the center of the x-axis and uses y for vertical
     *               placement
     * - "center-y": Places the existing image in the center of the y-axis and uses x for vertical
     *               placement
     *
     * @var string
     */
    private $mode = 'free';

    /**
     * X coordinate of the placement of the upper left corner of the existing image
     *
     * @var int
     */
    private $x = 0;

    /**
     * X coordinate of the placement of the upper left corner of the existing image
     *
     * @var int
     */
    private $y = 0;

    /**
     * Background color of the canvas. Defaults to white.
     *
     * @var string
     */
    private $bg = '#ffffff';

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'image.transformation.canvas' => 'transform',
        ];
    }

    /**
     * Transform the image
     *
     * @param EventInterface $event
     */
    public function transform(EventInterface $event) {
        $image = $event->getArgument('image');
        $params = $event->getArgument('params');

        $width  = !empty($params['width']) ? (int) $params['width'] : $image->getWidth();
        $height = !empty($params['height']) ? (int) $params['height'] : $image->getHeight();
        $mode   = !empty($params['mode']) ? $params['mode'] : $this->mode;
        $x      = !empty($params['x']) ? (int) $params['x'] : $this->x;
        $y      = !empty($params['y']) ? (int) $params['y'] : $this->y;
        $bg     = !empty($params['bg']) ? $this->formatColor($params['bg']) : $this->bg;

        try {
            // Clone the original that we will move back onto the canvas
            $original = clone $this->imagick;

            // Clear the original and make the canvas
            $this->imagick->clear();

            $this->imagick->newImage($width, $height, $bg);
            $this->imagick->setImageFormat($image->getExtension());

            $existingWidth = $image->getWidth();
            $existingHeight = $image->getHeight();

            if ($existingWidth > $width || $existingHeight > $height) {
                // The existing image is bigger than the canvas and needs to be cropped
                $cropX = 0;
                $cropY = 0;
                $cropWidth = $width;
                $cropHeight = $height;

                if ($existingWidth > $width) {
                    if ($mode === 'center' || $mode === 'center-x') {
                        $cropX = (int) ($existingWidth - $width) / 2;
                    }
                } else {
                    $cropWidth = $existingWidth;
                }

                if ($existingHeight > $height) {
                    if ($mode === 'center' || $mode === 'center-y') {
                        $cropY = (int) ($existingHeight - $height) / 2;
                    }
                } else {
                    $cropHeight = $existingHeight;
                }

                // Crop the original
                $original->cropImage($cropWidth, $cropHeight, $cropX, $cropY);
            }

            // Figure out the correct placement of the image based on the placement mode. Use the
            // size from the imagick image when calculating since the image may have been cropped
            // above.
            $existingSize = $original->getImageGeometry();

            if ($mode === 'center') {
                $x = ($width - $existingSize['width']) / 2;
                $y = ($height - $existingSize['height']) / 2;
            } else if ($mode === 'center-x') {
                $x = ($width - $existingSize['width']) / 2;
            } else if ($mode === 'center-y') {
                $y = ($height - $existingSize['height']) / 2;
            }

            // Paste existing image into the new canvas at the given position
            $this->imagick->compositeImage(
                $original,
                Imagick::COMPOSITE_DEFAULT,
                $x,
                $y
            );

            // Store the new image
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
