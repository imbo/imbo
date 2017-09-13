<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Image\Loader;

/**
 * Loader interface
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package Loaders
 */
interface LoaderInterface {
    /**
     * Get mime types supported by the loader
     *
     * Each element in the returned array represents a supported image format, and the keys are the
     * mime types of the image, and the values are an array with two keys:
     *
     *  - extension: file extension
     *  - callback: callable responsible for loading the image. The callable will receive two
     *              parameters:
     *               - \Imagick $imagick: The Imagick instance
     *               - string $blob: The image blob itself
     *
     * @return array[]
     */
    function getMimeTypeCallbacks();
}
