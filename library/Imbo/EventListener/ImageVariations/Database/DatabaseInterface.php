<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\EventListener\ImageVariations\Database;

use Imbo\Exception\DatabaseException;

/**
 * Database adapter interface for the image variations event listener
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Database
 */
interface DatabaseInterface {
    /**
     * Store an image variation
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier The image identifier of the original
     * @param int $width The width of the variation
     * @param int $height The height of the variation
     * @throws DatabaseException
     * @return boolean
     */
    function storeImageVariationMetadata($user, $imageIdentifier, $width, $height);

    /**
     * Fetch the best match of an image
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier The image identifier of the original
     * @param int $width The width we want to resize the image to
     * @return int|null Returns the closest width, or null
     */
    function getBestMatch($user, $imageIdentifier, $width);

    /**
     * Remove all metadata about image variations for an image
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier The image identifier
     * @param int $width Only delete the variation with this width
     * @return boolean
     */
    function deleteImageVariations($user, $imageIdentifier, $width = null);
}
