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
 * @link https://github.com/christeredvartsen/imbo
 */

namespace Imbo\Image\Transformation;

use Imbo\Image\ImageInterface;

use Imagine\Exception\Exception as ImagineException,
    Imagine\Image\Box as ImagineBox,
    Imagine\Image\Point as ImaginePoint,
    Imagine\Image\Color as ImagineColor,
    InvalidArgumentException;

/**
 * Canvas transformation
 *
 * @package Image
 * @subpackage Transformation
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
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
     * X coordinate of the placement of the upper left corner of the existing image
     *
     * @var int
     */
    private $x;

    /**
     * X coordinate of the placement of the upper left corner of the existing image
     *
     * @var int
     */
    private $y;

    /**
     * Background color of the canvas
     *
     * @var string
     */
    private $bg;

    /**
     * Class constructor
     *
     * @param int $width Width of the new canvas
     * @param int $height Height of the new canvas
     * @param int $x X coordinate of the placement of the upper left corner of the existing image
     * @param int $y Y coordinate of the placement of the upper left corner of the existing image
     * @param string $bg Background color of the canvas
     * @throws InvalidArgumentException
     */
    public function __construct($width, $height, $x = 0, $y = 0, $bg = null) {
        $this->width  = (int) $width;
        $this->height = (int) $height;
        $this->x = (int) $x;
        $this->y = (int) $y;
        $this->bg = $bg;
    }

    /**
     * @see Imbo\Image\Transformation\TransformationInterface::applyToImage()
     */
    public function applyToImage(ImageInterface $image) {
        try {
            $imagine = $this->getImagine();

            $background = $this->bg ? new ImagineColor($this->bg) : null;

            // Create a new canvas
            $canvas = $imagine->create(
                new ImagineBox($this->width, $this->height),
                $background
            );

            // Paste existing image into the new canvas at the given position
            $canvas->paste(
                $imagine->load($image->getBlob()),
                new ImaginePoint($this->x, $this->y)
            );

            // Store the new image
            $image->setBlob($canvas->get($image->getExtension()))
                  ->setWidth($this->width)
                  ->setHeight($this->height);
        } catch (ImagineException $e) {
            throw new Exception($e->getMessage(), 400, $e);
        }
    }
}

