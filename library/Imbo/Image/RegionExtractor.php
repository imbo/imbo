<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Image;

/**
 * Region extractor interface - transformations that implement this interface
 * can let Imbo know that the transformation will return a region of the input
 * image, given a set of parameters.
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Image
 */
interface RegionExtractor {
    /**
     * Get the region of the image that is extracted when applying the transformation
     * with the parameters provided.
     *
     * @param array $params Transformation parameters
     * @return array Array containing `width`, `height`, `x` and `y`
     */
    public function getExtractedRegion(array $params);
}
