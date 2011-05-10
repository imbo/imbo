<?php
/**
 * PHPIMS
 *
 * Copyright (c) 2011 Christer Edvartsen <cogo@starzinger.net>
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
 * @package PHPIMS
 * @subpackage ImageTransformation
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Image\Transformation;

use PHPIMS\Image;
use PHPIMS\Client\ImageUrl;
use PHPIMS\Image\TransformationInterface;

use \Imagine\Image\Point;
use \Imagine\Image\Box;

/**
 * Crop transformation
 *
 * @package PHPIMS
 * @subpackage ImageTransformation
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 * @see PHPIMS\Operation\Plugin\ManipulateImage
 */
class Crop implements TransformationInterface {
    /**
     * X coordinate of the top left corner of the crop
     *
     * @var int
     */
    private $x;

    /**
     * Y coordinate of the top left corner of the crop
     *
     * @var int
     */
    private $y;

    /**
     * Width of the crop
     *
     * @var int
     */
    private $width;

    /**
     * Height of the crop
     *
     * @var int
     */
    private $height;

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
     * @see PHPIMS\Image\TransformationInterface::applyToImage()
     */
    public function applyToImage(Image $image) {
        $imagineImage = $image->getImagineImage();
        $imagineImage->crop(
            new Point($this->x, $this->y),
            new Box($this->width, $this->height)
        );
        $image->refresh();
    }

    /**
     * @see PHPIMS\Image\TransformationInterface::applyToImageUrl()
     */
    public function applyToImageUrl(ImageUrl $url) {
        $params = array(
            'x=' . $this->x,
            'y=' . $this->y,
            'width=' . $this->width,
            'height=' . $this->height,
        );

        $url->append('crop:' . implode(',', $params));
    }
}