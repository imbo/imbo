<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Http\Response\Formatter;

use Imbo\Model,
    Imbo\Exception\InvalidArgumentException;

/**
 * Interface for formatters
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Response\Formatters
 */
interface ImageFormatterInterface {
    /**
     * Format an image model
     *
     * @param Model\Image $model The model to format
     * @return string Formatted data
     */
    function format(Model\Image $model);

    /**
     * Get the content type for the current formatter
     *
     * @return string
     */
    function getContentType();
}
