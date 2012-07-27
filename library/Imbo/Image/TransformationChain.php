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
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\Image;

use Imbo\Image\Transformation,
    Imbo\Image\Transformation\TransformationInterface,
    Iterator,
    Countable;

/**
 * Transformation collection
 *
 * @package Image
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class TransformationChain implements Iterator, Countable {
    /**
     * Transformations added
     *
     * @var array
     */
    private $transformations = array();

    /**
     * Position in the transformations array
     *
     * @var int
     */
    private $position = 0;

    /**
     * {@inheritdoc}
     */
    public function rewind() {
        $this->position = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function current() {
        return $this->transformations[$this->position];
    }

    /**
     * {@inheritdoc}
     */
    public function key() {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function next() {
        $this->position++;
    }

    /**
     * {@inheritdoc}
     */
    public function count() {
        return count($this->transformations);
    }

    /**
     * {@inheritdoc}
     */
    public function valid() {
        return isset($this->transformations[$this->position]);
    }

    /**
     * Apply all transformations to an image object
     *
     * @param ImageInterface $image Image object
     * @return TransformationChain
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
     * @param ImageInterface $image Image object
     * @param TransformationInterface $transformation Transformation object
     * @return TransformationChain
     */
    public function transformImage(ImageInterface $image, TransformationInterface $transformation) {
        $transformation->applyToImage($image);

        return $this;
    }

    /**
     * Add a transformation to the chain
     *
     * @param TransformationInterface $transformation The transformation to add
     * @return TransformationChain
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
     * @return TransformationChain
     * @see Transformation\Border
     */
    public function border($color = null, $width = null, $height = null) {
        return $this->add(new Transformation\Border($color, $width, $height));
    }

    /**
     * MaxSize transformation
     *
     * @param int $maxWidth Max width of the image
     * @param int $maxHeight Max height of the image
     * @return TransformationChain
     * @see Transformation\MaxSize
     */
    public function maxSize($maxWidth = null, $maxHeight = null) {
        return $this->add(new Transformation\MaxSize($maxWidth, $maxHeight));
    }

    /**
     * Compression transformation
     *
     * @param int $quality Quality of the resulting image
     * @return TransformationChain
     * @see Transformation\Compress
     */
    public function compress($quality) {
        return $this->add(new Transformation\Compress($quality));
    }

    /**
     * Crop transformation
     *
     * @param int $x X coordinate of the top left corner of the crop
     * @param int $y Y coordinate of the top left corner of the crop
     * @param int $width Width of the crop
     * @param int $height Height of the crop
     * @return TransformationChain
     * @see Transformation\Crop
     */
    public function crop($x, $y, $width, $height) {
        return $this->add(new Transformation\Crop($x, $y, $width, $height));
    }

    /**
     * Rotate transformation
     *
     * @param int $angle Angle of the rotation
     * @param string $bg Background color
     * @return TransformationChain
     * @see Transformation\Rotate
     */
    public function rotate($angle, $bg = null) {
        return $this->add(new Transformation\Rotate($angle, $bg));
    }

    /**
     * Resize transformation
     *
     * @param int $width Width of the resize
     * @param int $height Height of the resize
     * @return TransformationChain
     * @see Transformation\Resize
     */
    public function resize($width = null, $height = null) {
        return $this->add(new Transformation\Resize($width, $height));
    }

    /**
     * Thumbnail transformation
     *
     * @param int $width Width of the thumbnail
     * @param int $height height of the thumbnail
     * @param string $fit Fit style ('inset' or 'outbound')
     * @return TransformationChain
     * @see Transformation\Thumbnail
     */
    public function thumbnail($width = null, $height = null, $fit = null) {
        return $this->add(new Transformation\Thumbnail($width, $height, $fit));
    }

    /**
     * Flip horizontally transformation
     *
     * @return TransformationChain
     * @see Transformation\FlipHorizontally
     */
    public function flipHorizontally() {
        return $this->add(new Transformation\FlipHorizontally());
    }

    /**
     * Flip vertically transformation
     *
     * @return TransformationChain
     * @see Transformation\FlipVertically
     */
    public function flipVertically() {
        return $this->add(new Transformation\FlipVertically());
    }

    /**
     * Canvas transformation
     *
     * @param int $width Width of the new canvas
     * @param int $height Height of the new canvas
     * @param string $mode The placement mode
     * @param int $x X coordinate of the placement of the upper left corner of the existing image
     * @param int $y Y coordinate of the placement of the upper left corner of the existing image
     * @param string $bg Background color of the canvas
     * @return TransformationChain
     * @see Transformation\Canvas
     */
    public function canvas($width, $height, $mode = null, $x = null, $y = null, $bg = null) {
        return $this->add(new Transformation\Canvas($width, $height, $mode, $x, $y, $bg));
    }

    /**
     * Transpose transformation
     *
     * @return TransformationChain
     * @see Transformation\Transpose
     */
    public function transpose() {
        return $this->add(new Transformation\Transpose());
    }

    /**
     * Transverse transformation
     *
     * @return TransformationChain
     * @see Transformation\Transverse
     */
    public function transverse() {
        return $this->add(new Transformation\Transverse());
    }
}
