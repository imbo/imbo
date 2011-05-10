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
class Resize implements TransformationInterface {
    /**
     * Width of the resize
     *
     * @var int
     */
    private $width;

    /**
     * Height of the resize
     *
     * @var int
     */
    private $height;

    /**
     * Class constructor
     *
     * @param int $width Width of the resize
     * @param int $height Height of the resize
     */
    public function __construct($width = null, $height = null) {
        $this->width  = (int) $width;
        $this->height = (int) $height;
    }

    /**
     * @see PHPIMS\Image\TransformationInterface::applyToImage()
     */
    public function applyToImage(Image $image) {
        $imagineImage = $image->getImagineImage();
        $width  = $this->width ?: null;
        $height = $this->height ?: null;

        // Fetch the size of the original image
        $size = $imagineImage->getSize();

        // Calculate width or height if not both have been specified
        if (!$height) {
            $height = ($size->getHeight() / $size->getWidth()) * $width;
        } else if (!$width) {
            $width = ($size->getWidth() / $size->getHeight()) * $height;
        }

        // Resize image and store in the image object
        $imagineImage->resize(new Box($width, $height));
        $image->refresh();
    }

    /**
     * @see PHPIMS\Image\TransformationInterface::applyToImageUrl()
     */
    public function applyToImageUrl(ImageUrl $url) {
        $params = array();

        if ($this->width !== null) {
            $params[] = 'width=' . $this->width;
        }

        if ($this->height !== null) {
            $params[] = 'height=' . $this->height;
        }

        $url->append('resize:' . implode(',', $params));
    }
}