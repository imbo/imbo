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

use PHPIMS\Image\TransformationInterface;
use \Imagine\ImageInterface;
use \Imagine\Image\Color;
use \Imagine\Image\Point;

/**
 * Border transformation
 *
 * @package PHPIMS
 * @subpackage ImageTransformation
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 * @see PHPIMS\Operation\Plugin\ManipulateImage
 */
class Border implements TransformationInterface {
    /**
     * Width of the border
     *
     * @var int
     */
    private $width;

    /**
     * Height of the border
     *
     * @var int
     */
    private $height;

    /**
     * Color of the border
     *
     * @var string
     */
    private $color;

    /**
     * Class constructor
     *
     * @param string $color The color to set
     * @param int $width Width of the border
     * @param int $height Height of the border
     */
    public function __construct($color = '000', $width = 1, $height = 1) {
        $this->color  = $color;
        $this->width  = (int) $width;
        $this->height = (int) $height;
    }

    /**
     * @see PHPIMS\Image\TransformationInterface::applyToImage()
     */
    public function applyToImage(ImageInterface $image) {
        $color  = new Color($this->color);
        $size   = $image->getSize();
        $width  = $size->getWidth();
        $height = $size->getHeight();
        $draw   = $image->draw();

        // Draw top and bottom lines
        for ($i = 0; $i < $this->height; $i++) {
            $draw->line(new Point(0, $i), new Point($width - 1, $i), $color)
                 ->line(new Point($width - 1, $height - ($i + 1)), new Point(0, $height - ($i + 1)), $color);
        }

        // Draw sides
        for ($i = 0; $i < $this->width; $i++) {
            $draw->line(new Point($i, 0), new Point($i, $height - 1), $color)
                 ->line(new Point($width - ($i + 1), 0), new Point($width - ($i + 1), $height - 1), $color);
        }
    }

    /**
     * @see PHPIMS\Image\TransformationInterface::getUrlTrigger()
     */
    public function getUrlTrigger() {
        $params = array(
            'color=' . $this->color,
            'width=' . $this->width,
            'height=' . $this->height,
        );

        return 'border:' . implode(',', $params);
    }
}