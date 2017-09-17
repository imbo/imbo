<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Image\InputLoader;

use Imagick;

/**
 * Input loader interface
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package Loaders
 */
interface InputLoaderInterface {
    /**
     * Get mime types supported by the loader
     *
     * Each element in the returned array represents a supported image format, with the mime types
     * as the key and the extension as the value.
     *
     * @return array
     */
    function getSupportedMimeTypes();

    /**
     * Load data from a blob in a specific format into the provided Imagick instance.
     *
     * @param Imagick $imagick Imagick instance to populate with rasterized image data
     * @param string $blob The file being loaded as a binary blob
     * @param string $mimeType The determined mime type of the file. Will match one of the mime
     *                         types specified in `getSupportedMimeTypes()`.
     * @return mixed Return false to have the input loader manager try the next loader. All other
     *               return values (including null / void) means that the loader successfully
     *               managed to load the image.
     */
    function load(Imagick $imagick, $blob, $mimeType);
}
