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
    Imagick,
    ImagickException,
    ImagickPixelException;

/**
 * Canvas transformation
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
class Canvas extends Transformation implements TransformationInterface {
    /**
     * Width of the canvas
     *
     * @var int
     */
    private $width;

    /**
     * Height of the canvas
     *
     * @var int
     */
    private $height;

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
     * Class constructor
     *
     * @param array $params Parameters for this transformation
     */
    public function __construct(array $params) {
        $this->width = !empty($params['width']) ? (int) $params['width'] : 0;
        $this->height = !empty($params['height']) ? (int) $params['height'] : 0;

        if (!empty($params['mode'])) {
            $this->mode = $params['mode'];
        }

        if (!empty($params['x'])) {
            $this->x = (int) $params['x'];
        }

        if (!empty($params['y'])) {
            $this->y = (int) $params['y'];
        }

        if (!empty($params['bg'])) {
            $this->bg = $this->formatColor($params['bg']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function applyToImage(Image $image) {
        try {
            if (!$this->width) {
                $this->width = $image->getWidth();
            }

            if (!$this->height) {
                $this->height = $image->getHeight();
            }

            // Create a new canvas
            $canvas = new Imagick();
            $canvas->newImage($this->width, $this->height, $this->bg);
            $canvas->setImageFormat($image->getExtension());

            // Load existing image
            $existingImage = $this->getImagick();
            $existingImage->readImageBlob($image->getBlob());

            $existingWidth = $image->getWidth();
            $existingHeight = $image->getHeight();

            if ($existingWidth > $this->width || $existingHeight > $this->height) {
                // The existing image is bigger than the canvas and needs to be cropped
                $cropX = 0;
                $cropY = 0;
                $cropWidth = $this->width;
                $cropHeight = $this->height;

                if ($existingWidth > $this->width) {
                    if ($this->mode === 'center' || $this->mode === 'center-x') {
                        $cropX = (int) ($existingWidth - $this->width) / 2;
                    }
                } else {
                    $cropWidth = $existingWidth;
                }

                if ($existingHeight > $this->height) {
                    if ($this->mode === 'center' || $this->mode === 'center-y') {
                        $cropY = (int) ($existingHeight - $this->height) / 2;
                    }
                } else {
                    $cropHeight = $existingHeight;
                }

                $existingImage->cropImage($cropWidth, $cropHeight, $cropX, $cropY);
            }

            // Default placement
            $x = $this->x;
            $y = $this->y;

            // Figure out the correct placement of the image based on the placement mode. Use the
            // size from the imagick image when calculating since the image may have been cropped
            // above.
            $existingSize = $existingImage->getImageGeometry();

            if ($this->mode === 'center') {
                $x = ($this->width - $existingSize['width']) / 2;
                $y = ($this->height - $existingSize['height']) / 2;
            } else if ($this->mode === 'center-x') {
                $x = ($this->width - $existingSize['width']) / 2;
            } else if ($this->mode === 'center-y') {
                $y = ($this->height - $existingSize['height']) / 2;
            }

            // Paste existing image into the new canvas at the given position
            $canvas->compositeImage(
                $existingImage,
                Imagick::COMPOSITE_DEFAULT,
                $x,
                $y
            );

            // Store the new image
            $size = $canvas->getImageGeometry();
            $image->setBlob($canvas->getImageBlob())
                  ->setWidth($size['width'])
                  ->setHeight($size['height']);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        } catch (ImagickPixelException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }
}

