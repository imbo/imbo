<?php
/**
 * Imbo
 *
 * Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * * The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package Image
 * @subpackage Transformation
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\Image\Transformation;

use Imbo\Image\ImageInterface,
    Imbo\Exception\TransformationException,
    Imagick,
    ImagickException,
    ImagickPixelException;

/**
 * Canvas transformation
 *
 * @package Image
 * @subpackage Transformation
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
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
    private $bg = '#fff';

    /**
     * Class constructor
     *
     * @param int $width Width of the new canvas
     * @param int $height Height of the new canvas
     * @param string $mode The placement mode
     * @param int $x X coordinate of the placement of the upper left corner of the existing image
     * @param int $y Y coordinate of the placement of the upper left corner of the existing image
     * @param string $bg Background color of the canvas
     */
    public function __construct($width, $height, $mode = 'free', $x = 0, $y = 0, $bg = null) {
        $this->width  = (int) $width;
        $this->height = (int) $height;

        if ($mode) {
            $this->mode = $mode;
        }

        if ($x) {
            $this->x = (int) $x;
        }

        if ($y) {
            $this->y = (int) $y;
        }

        if ($bg) {
            $this->bg = $this->formatColor($bg);
        }
    }

    /**
     * @see Imbo\Image\Transformation\TransformationInterface::applyToImage()
     */
    public function applyToImage(ImageInterface $image) {
        try {
            // Create a new canvas
            $canvas = new Imagick();
            $canvas->newImage($this->width, $this->height, $this->bg);
            $canvas->setImageFormat($image->getExtension());

            // Load existing image
            $existingImage  = $this->getImagick();
            $existingImage->readImageBlob($image->getBlob());
            $existingWidth  = $image->getWidth();
            $existingHeight = $image->getHeight();

            if ($existingWidth > $this->width || $existingHeight > $this->height) {
                // The existing image is bigger than the canvas and needs to be cropped
                $cropX = 0;
                $cropY = 0;
                $cropWidth  = $this->width;
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

