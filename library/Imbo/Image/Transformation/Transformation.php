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

namespace Imbo\Image\Transformation;

use Imagine\Image\ImagineInterface,
    Imagine\Imagick\Imagine;

/**
 * Abstract transformation
 *
 * @package Image
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */
abstract class Transformation implements TransformationInterface {
    /**
     * Imagine instance
     *
     * @var Imagine\Image\ImagineInterface
     */
    private $imagine;

    /**
     * Imagick instance
     *
     * @var Imagick
     */
    private $imagick;

    /**
     * @see Imbo\Image\Transformation\TransformationInterface::getImagick()
     */
    public function getImagick() {
        if ($this->imagick === null) {
            $this->imagick = new \Imagick();
        }

        return $this->imagick;
    }

    /**
     * @see Imbo\Image\Transformation\TransformationInterface::getImagick()
     */
    public function setImagick(Imagick $imagick) {
        $this->imagick = $imagick;

        return $this;
    }

    /**
     * @see Imbo\Image\Transformation\TransformationInterface::getImagine()
     */
    public function getImagine() {
        if ($this->imagine === null) {
            $this->imagine = new Imagine();
        }

        return $this->imagine;
    }

    /**
     * @see Imbo\Image\Transformation\TransformationInterface::getImagine()
     */
    public function setImagine(ImagineInterface $imagine) {
        $this->imagine = $imagine;

        return $this;
    }
}
