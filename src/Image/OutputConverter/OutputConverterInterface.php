<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Image\OutputConverter;

use Imbo\Model\Image;
use Imagick;

/**
 * Loader interface
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package Image\OutputConverters
 */
interface OutputConverterInterface {
    /**
     * Get mime types supported by the output converter
     *
     * Each element in the returned array represents a supported image format, with the
     * mime type as the key and the extension as the value.
     *
     * @return array[]
     */
    function getSupportedMimeTypes();

    /**
     * Load data from a blob in a specific format into the provided Imagick instance.
     *
     * Return false on failure.
     *
     * @param Imagick $imagick Imagick instance to populate with rasterized image data
     * @param Image $image The Image model to render from Imbo
     * @param string $extension The extension requested through imbo. Will match one of the extension specified in `getSupportedMimeTypes()`.
     * @param string $mimeType Mime type of the file being output.
     * @return null|boolean|Imagick
     */
    function convert(Imagick $imagick, Image $image, $extension, $mimeType);
}
