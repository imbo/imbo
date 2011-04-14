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

namespace PHPIMS\Operation\Plugin;

use PHPIMS\Operation\Plugin;
use PHPIMS\Operation;

/**
 * Identify image plugin
 *
 * This plugin will identify the mime type of the current image in the response object (using the
 * fileinfo extension).
 *
 * @package PHPIMS
 * @subpackage OperationPlugin
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class IdentifyImagePlugin extends Plugin {
    /**
     * Supported mime types and the correct file extension
     *
     * @var array
     */
    static public $mimeTypes = array(
        'image/png'  => 'png',
        'image/jpeg' => 'jpeg',
        'image/gif'  => 'gif',
    );

    /**
     * @see PHPIMS\Operation\Plugin::$events
     */
    static public $events = array(
        'getImagePostExec' => 100,
        'addImagePreExec'  => 102,
    );

    /**
     * @see PHPIMS\Operation\Plugin::exec()
     */
    public function exec(Operation $operation) {
        $image = $operation->getImage();

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($image->getBlob());

        if (!static::supportedMimeType($mime)) {
            throw new Exception('Unsupported image type: ' . $mime, 415);
        }

        $extension = static::getFileExtension($mime);

        $image->setMimeType($mime)
              ->setExtension($extension);

        // Update hash in case it has a wrong extension
        $hash = $operation->getHash();
        $hash = substr($hash, 0, 32) . '.' . $extension;
        $operation->setHash($hash);
    }

    /**
     * Check if a mime type is supported by PHPIMS
     *
     * @param string $mime The mime type to check. For instance "image/png"
     * @return boolean
     */
    static public function supportedMimeType($mime) {
        return isset(static::$mimeTypes[$mime]);
    }

    /**
     * Get the file extension mapped to a mime type
     *
     * @param string $mime The mime type. For instance "image/png"
     * @return boolean|string The extension (without the leading dot) on success or boolean false
     *                        if the mime type is not supported.
     */
    static public function getFileExtension($mime) {
        return isset(static::$mimeTypes[$mime]) ? static::$mimeTypes[$mime] : false;
    }
}