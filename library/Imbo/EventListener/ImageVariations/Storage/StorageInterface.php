<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\EventListener\ImageVariations\Storage;

/**
 * Storage adapter interface for the image variations event listener
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Storage
 */
interface StorageInterface {
    /**
     * Store an image variation
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier The image identifier of the original
     * @param string $blob The image blob to store
     * @param int $width The width of the variation
     * @return boolean
     */
    function storeImageVariation($user, $imageIdentifier, $blob, $width);

    /**
     * Get the blob of an image variation
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier The image identifier of the original
     * @param int $width The width of the variation
     * @return string
     */
    function getImageVariation($user, $imageIdentifier, $width);

    /**
     * Remove an image variation
     *
     * @param string $user The user which the image belongs to
     * @param string $imageIdentifier The image identifier
     * @param int $width Only delete the variation with this width
     * @return boolean
     */
    function deleteImageVariations($user, $imageIdentifier, $width = null);
}
