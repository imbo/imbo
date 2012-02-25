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
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\Image\Transformation;

use Imbo\Image\ImageInterface,
    Imbo\Exception\TransformationException,
    Imagine\Exception\Exception as ImagineException,
    Imagine\Image\Box;

/**
 * MaxSize transformation
 *
 * @package Image
 * @subpackage Transformation
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
class MaxSize extends Transformation implements TransformationInterface {
    /**
     * Max width of the image
     *
     * @var int
     */
    private $maxWidth;

    /**
     * Max height of the image
     *
     * @var int
     */
    private $height;

    /**
     * Class constructor
     *
     * @param int $maxWidth Max width of the image
     * @param int $maxHeight Height of the image
     */
    public function __construct($maxWidth = null, $maxHeight = null) {
        $this->maxWidth  = (int) $maxWidth;
        $this->maxHeight = (int) $maxHeight;
    }

    /**
     * @see Imbo\Image\Transformation\TransformationInterface::applyToImage()
     */
    public function applyToImage(ImageInterface $image) {
        try {
            $imagine = $this->getImagine();
            $imagineImage = $imagine->load($image->getBlob());

            // Fetch the size of the original image
            $size = $imagineImage->getSize();

            $width  = $this->maxWidth  ?: $size->getWidth();
            $height = $this->maxHeight ?: $size->getHeight();

            // Figure out original ratio
            $ratio = $size->getWidth() / $size->getHeight();

            // Is the original image larger than the max-parameters?
            if (($size->getWidth() > $width) || ($size->getHeight() > $height)) {
                if (($width / $height) > $ratio) {
                    $width  = round($height * $ratio);
                } else {
                    $height = round($width / $ratio);
                }
            } else {
                // Original image is smaller than the max-parameters, don't transform
                return;
            }

            $imagineImage->resize(new Box($width, $height));

            $box = $imagineImage->getSize();

            $image->setBlob($imagineImage->get($image->getExtension()))
                  ->setWidth($box->getWidth())
                  ->setHeight($box->getHeight());
        } catch (ImagineException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }
}
