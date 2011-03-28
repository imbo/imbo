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
 * @subpackage OperationPlugin
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

/**
 * Manipulate image plugin
 *
 * This plugin enabled image manipulation given query parameters.
 *
 * @package PHPIMS
 * @subpackage OperationPlugin
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class PHPIMS_Operation_Plugin_ManipulateImagePlugin extends PHPIMS_Operation_Plugin_Abstract {
    /**
     * @see PHPIMS_Operation_Plugin_Abstract::$events
     */
    static public $events = array(
        'getImagePostExec' => 101,
    );

    /**
     * @see PHPIMS_Operation_Plugin_Abstract::exec()
     */
    public function exec() {
        if (isset($_GET['width']) || isset($_GET['height'])) {
            $width  = (isset($_GET['width']) ? $_GET['width'] : 0);
            $height = (isset($_GET['height']) ? $_GET['height'] : 0);

            // Fetch the original image object
            $originalImage = $this->getOperation()->getImage();

            // Load the image into imagine
            $imagine = new \Imagine\Imagick\Imagine();
            $image = $imagine->load($originalImage->getBlob());

            // Fetch the size of the original image
            $size = $image->getSize();

            // Calculate width or height if not both have been specified
            if (!$height) {
                $height = ($size->getHeight() / $size->getWidth()) * $width;
            } else if (!$width) {
                $width = ($size->getWidth() / $size->getHeight()) * $height;
            }

            // Resize image and store in the image object
            $image->resize(new \Imagine\Image\Box($width, $height));
            $originalImage->setBlob((string) $image);
        }
    }
}