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
 * Input size contraint interface - transformations that implement this interface
 * can let Imbo know the minimum size of the input image that it can receive,
 * given a set of parameters.
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Image
 */
interface InputSizeConstraint {
    /**
     * Get the minimum input size that this transformation can accept as input, given the
     * parameters provided. A transformation can return `null` if it has no preference
     * about the input size, or `false` if the minimum input size can't be calculated.
     *
     * Note that returning `false` will stop the transformation manager from trying to
     * find a smaller minimum input size for the transformations that follow the current,
     * which is what you want if for instance an image is rotated in an angle which makes
     * calculation of the resulting image hard - due to other transformations being applied
     * further down the transformation chain.
     *
     * @param array $params Transformation parameters
     * @param array $imageSize Size of the image
     * @return array Array containing `width` and `height`
     */
    public function getMinimumInputSize(array $params, array $imageSize);
}
