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
    ImagickException;

/**
 * Resize transformation
 *
 * @package Image
 * @subpackage Transformation
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class Resize extends Transformation implements TransformationInterface {
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
     * @see Imbo\Image\Transformation\TransformationInterface::applyToImage()
     */
    public function applyToImage(ImageInterface $image) {
        try {
            $width  = $this->width  ?: null;
            $height = $this->height ?: null;

            // Calculate width or height if not both have been specified
            if (!$height) {
                $height = ($image->getHeight() / $image->getWidth()) * $width;
            } else if (!$width) {
                $width = ($image->getWidth() / $image->getHeight()) * $height;
            }

            $imagick = $this->getImagick();
            $imagick->setOption('jpeg:size', $width . 'x' . $height);
            $imagick->readImageBlob($image->getBlob());
            $imagick->cropThumbnailImage($width, $height);

            $size = $imagick->getImageGeometry();

            $image->setBlob($imagick->getImageBlob())
                  ->setWidth($size['width'])
                  ->setHeight($size['height']);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }
}
