<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Storage;

/**
 * Image reader aware interface
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Storage
 */
interface ImageReaderAware {
    /**
     * Set an instance of an image reader
     *
     * @param ImageReader $reader An image reader instance
     */
    function setImageReader(ImageReader $reader);

    /**
     * Get an instance of an image reader
     *
     * @return ImageReader An image reader instance
     */
    function getImageReader();
}
