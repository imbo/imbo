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
 * @subpackage Interfaces
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Image;

/**
 * Image interface
 *
 * @package PHPIMS
 * @subpackage Interfaces
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
interface ImageInterface {
    /**
     * Get the size of the image data in bytes
     *
     * @return int
     */
    function getFilesize();

    /**
     * Get the mime type
     *
     * @return string
     */
    function getMimeType();

    /**
     * Set the mime type
     *
     * @param string $mimeType The mime type, for instance "image/png"
     * @return PHPIMS\Image\ImageInterface
     */
    function setMimeType($mimeType);

    /**
     * Get the extension
     *
     * @return string
     */
    function getExtension();

    /**
     * Set the extension
     *
     * @param string $extension The file extension
     * @return PHPIMS\Image\ImageInterface
     */
    function setExtension($extension);


    /**
     * Get the blob
     *
     * @return string
     */
    function getBlob();

    /**
     * Set the blob
     *
     * This method should update the size property of the image (returned by getSize()).
     *
     * @param string $blob The binary data to set
     * @return PHPIMS\Image\ImageInterface
     */
    function setBlob($blob);

    /**
     * Get the metadata
     *
     * @return array
     */
    function getMetadata();

    /**
     * Set the metadata
     *
     * @param array $metadata An array with metadata
     * @return PHPIMS\Image\ImageInterface
     */
    function setMetadata(array $metadata);

    /**
     * Get the width
     *
     * @return int
     */
    function getWidth();

    /**
     * Set the width
     *
     * @param int $width Width in pixels
     * @return PHPIMS\Image\ImageInterface
     */
    function setWidth($width);

    /**
     * Get the height
     *
     * @return int
     */
    function getHeight();

    /**
     * Set the height
     *
     * @param int $height Height in pixels
     * @return PHPIMS\Image\ImageInterface
     */
    function setHeight($height);
}
