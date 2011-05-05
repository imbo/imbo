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
use PHPIMS\Image\Transformation;
use \Imagine\ImageInterface;
use \Imagine\Image\Box;

/**
 * Resize transformation
 *
 * @package PHPIMS
 * @subpackage ImageTransformation
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 * @see PHPIMS\Operation\Plugin\ManipulateImage
 */
class Resize extends Transformation implements TransformationInterface {
    /**
     * Width of the resize
     *
     * @var int
     */
    private $width = null;

    /**
     * Height of the resize
     *
     * @var int
     */
    private $height = null;

    /**
     * Class constructor
     *
     * @param int $width Width of the resize
     * @param int $height Height of the resize
     * @throws PHPIMS\Image\Transformation\Exception
     */
    public function __construct($width = null, $height = null) {
        if ($width === null && $height === null) {
            throw new FilterException('$width and/or $height must be set');
        }

        $this->width  = $width;
        $this->height = $height;
    }

    /**
     * @see PHPIMS\Image\TransformationInterface::apply()
     */
    public function apply(ImageInterface $image) {
        if (empty($this->width) && empty($this->height)) {
            throw new Exception('Missing parameters width and/or height');
        }

        $width  = (empty($this->width) ? (int) $this->width : 0);
        $height = (empty($this->height) ? (int) $this->height : 0);

        // Fetch the size of the original image
        $size = $image->getSize();

        // Calculate width or height if not both have been specified
        if (!$height) {
            $height = ($size->getHeight() / $size->getWidth()) * $width;
        } else if (!$width) {
            $width = ($size->getWidth() / $size->getHeight()) * $height;
        }

        // Resize image and store in the image object
        $image->resize(new Box($width, $height));
    }

    /**
     * @see PHPIMS\Image\TransformationInterface::getUrlTrigger()
     */
    public function getUrlTrigger() {
        $params = array();

        if ($this->width !== null) {
            $params[] = 'width=' . $this->width;
        }

        if ($this->height !== null) {
            $params[] = 'height=' . $this->height;
        }

        return 'resize:' . implode(',', $params);
    }
}