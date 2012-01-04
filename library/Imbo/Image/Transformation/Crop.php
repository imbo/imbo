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

use Imagine\Exception\Exception as ImagineException;
use Imagine\Image\Point;
use Imagine\Image\Box;

/**
 * Crop transformation
 *
 * @package Image
 * @subpackage Transformation
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */
class Crop extends Transformation implements TransformationInterface {
    /**
     * X coordinate of the top left corner of the crop
     *
     * @var int
     */
    public $x;

    /**
     * Y coordinate of the top left corner of the crop
     *
     * @var int
     */
    public $y;

    /**
     * Width of the crop
     *
     * @var int
     */
    public $width;

    /**
     * Height of the crop
     *
     * @var int
     */
    public $height;

    /**
     * Class constructor
     *
     * @param int $x X coordinate of the top left corner of the crop
     * @param int $y Y coordinate of the top left corner of the crop
     * @param int $width Width of the crop
     * @param int $height Height of the crop
     */
    public function __construct($x, $y, $width, $height) {
        $this->x      = (int) $x;
        $this->y      = (int) $y;
        $this->width  = (int) $width;
        $this->height = (int) $height;
    }

    /**
     * @see Imbo\Image\Transformation\TransformationInterface::applyToImage()
     */
    public function applyToImage(ImageInterface $image) {
        try {
            $imagine = $this->getImagine();
            $imagineImage = $imagine->load($image->getBlob());

            $imagineImage->crop(
                new Point($this->x, $this->y),
                new Box($this->width, $this->height)
            );

            $image->setBlob($imagineImage->get($image->getExtension()))
                  ->setWidth($this->width)
                  ->setHeight($this->height);
        } catch (ImagineException $e) {
            throw new Exception($e->getMessage(), 400, $e);
        }
    }
}
