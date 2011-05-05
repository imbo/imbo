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
 * @subpackage Image
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Image;

use PHPIMS\Image\Transformation\Border;
use PHPIMS\Image\Transformation\Crop;
use PHPIMS\Image\Transformation\Resize;
use PHPIMS\Image\Transformation\Rotate;
use PHPIMS\Image\Transformation\Thumbnail;
use PHPIMS\Image\Transformation\FlipHorizontally;
use PHPIMS\Image\Transformation\FlipVertically;

use PHPIMS\Client\ImageUrl;
use \Imagine\ImageInterface;

/**
 * Transformation collection
 *
 * @package PHPIMS
 * @subpackage Image
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class TransformationChain {
    /**
     * Transformations added
     *
     * @var array
     */
    private $transformations = array();

    /**
     * Apply all transformations to an image url object
     *
     * @param PHPIMS\Client\ImageUrl $url Instance of the image url
     * @return PHPIMS\Image\TransformationChain
     */
    public function applyToImageUrl(ImageUrl $url) {
        foreach ($this->transformations as $transformation) {
            $this->transformImageUrl($url, $transformation);
        }

        return $this;
    }

    /**
     * Transform an image url
     *
     * @param PHPIMS\Client\ImageUrl $url Image url object
     * @param PHPIMS\Image\TransformationInterface $transformation Transformation object
     * @return PHPIMS\Image\TransformationChain
     */
    public function transformImageUrl(ImageUrl $url, TransformationInterface $transformation) {
        $url->append($transformation->getUrlTrigger());

        return $this;
    }

    /**
     * Apply all transformations to an image object
     *
     * @param \Imagine\ImageInterface $image Image object
     * @return PHPIMS\Image\TransformationChain
     */
    public function applyToImage(ImageInterface $image) {
        foreach ($this->transformations as $transformation) {
            $this->transformImage($image, $transformation);
        }

        return $this;
    }

    /**
     * Transform an image
     *
     * @param \Imagine\ImageInterface $image Image object
     * @param PHPIMS\Image\TransformationInterface $transformation Transformation object
     * @return PHPIMS\Image\TransformationChain
     */
    public function transformImage(ImageInterface $image, TransformationInterface $transformation) {
        $transformation->applyToImage($image);

        return $this;
    }

    /**
     * Add a transformation to the chain
     *
     * @param TransformationInterface $transformation The transformation to add
     * @return PHPIMS\Image\TransformationChain
     */
    public function add(TransformationInterface $transformation) {
        $this->transformations[] = $transformation;

        return $this;
    }

    /**
     * Border transformation
     *
     * @param string $color The color to use
     * @param int $width Width of the border
     * @param int $height Height of the border
     * @return PHPIMS\Image\TransformationChain
     */
    public function border($color = null, $width = null, $height = null) {
        return $this->add(new Border($color, $width, $height));
    }

    /**
     * Crop transformation
     *
     * @param int $x X coordinate of the top left corner of the crop
     * @param int $y Y coordinate of the top left corner of the crop
     * @param int $width Width of the crop
     * @param int $height Height of the crop
     * @return PHPIMS\Image\TransformationChain
     */
    public function crop($x, $y, $width, $height) {
        return $this->add(new Crop($x, $y, $width, $height));
    }

    /**
     * Rotate transformation
     *
     * @param int $angle Angle of the rotation
     * @param string $bg Background color
     * @return PHPIMS\Image\TransformationChain
     */
    public function rotate($angle, $bg = null) {
        return $this->add(new Rotate($angle, $bg));
    }

    /**
     * Resize transformation
     *
     * @param int $width Width of the resize
     * @param int $height Height of the resize
     * @return PHPIMS\Image\TransformationChain
     */
    public function resize($width = null, $height = null) {
        return $this->add(new Resize($width, $height));
    }

    /**
     * Thumbnail transformation
     *
     * @param int $width Width of the thumbnail
     * @param int $height height of the thumbnail
     * @param string $fit Fit style ('inset' or 'outbound')
     * @return PHPIMS\Image\TransformationChain
     */
    public function thumbnail($width = null, $height = null, $fit = null) {
        return $this->add(new Thumbnail($width, $height, $fit));
    }

    /**
     * Flip horizontally transformation
     *
     * @return PHPIMS\Image\TransformationChain
     */
    public function flipHorizontally() {
        return $this->add(new FlipHorizontally());
    }

    /**
     * Flip vertically transformation
     *
     * @return PHPIMS\Image\TransformationChain
     */
    public function flipVertically() {
        return $this->add(new FlipVertically());
    }
}