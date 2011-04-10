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

/**
 * Border transformation
 *
 * @package PHPIMS
 * @subpackage ImageTransformation
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 * @see PHPIMS_Operation_Plugin_ManipulateImagePlugin
 */
class PHPIMS_Operation_Plugin_ManipulateImagePlugin_Transformation_Border extends
      PHPIMS_Operation_Plugin_ManipulateImagePlugin_Transformation_Abstract {
    /**
     * @see PHPIMS_Operation_Plugin_ManipulateImagePlugin_Transformation_Interface::apply()
     */
    public function apply(\Imagine\Imagick\Image $image, array $params = array()) {
        if (!isset($params['color'])) {
            $params['color'] = '000';
        }

        if (!isset($params['width'])) {
            $params['width'] = 1;
        }

        if (!isset($params['height'])) {
            $params['height'] = 1;
        }

        $color = new \Imagine\Image\Color($params['color']);
        $width = $image->getSize()->getWidth();
        $height = $image->getSize()->getHeight();

        // Draw top and bottom lines
        for ($i = 0; $i < $params['height']; $i++) {
            $image->draw()->line(new \Imagine\Image\Point(0, $i), new \Imagine\Image\Point($width - 1, $i), $color)
                          ->line(new \Imagine\Image\Point($width - 1, $height - ($i + 1)), new \Imagine\Image\Point(0, $height - ($i + 1)), $color);
        }

        // Draw sides
        for ($i = 0; $i < $params['width']; $i++) {
            $image->draw()->line(new \Imagine\Image\Point($i, 0), new \Imagine\Image\Point($i, $height - 1), $color)
                          ->line(new \Imagine\Image\Point($width - ($i + 1), 0), new \Imagine\Image\Point($width - ($i + 1), $height - 1), $color);
        }
    }
}