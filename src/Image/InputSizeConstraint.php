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
 * given a set of parameters. It can also be used to signal that the parameters
 * provided to this transformation needs to be adjusted if the input size has changed,
 * for instance when the ImageVariations-listener is used.
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Image
 */
interface InputSizeConstraint {
    const NO_TRANSFORMATION = 0;
    const STOP_RESOLVING = 1;

    /**
     * Get the minimum input size that this transformation can accept as input, given the
     * parameters provided. A transformation can return InputSizeConstraint::NO_TRANSFORMATION if it
     * has no preference about the input size, or InputSizeConstraint::STOP_RESOLVING if the minimum
     * input size can't be calculated.
     *
     * Note that returning InputSizeConstraint::STOP_RESOLVING will stop the transformation manager
     * from trying to find a smaller minimum input size for the transformations that follow the
     * current, which is what you want if for instance an image is rotated in an angle which makes
     * calculation of the resulting image hard - due to other transformations being applied
     * further down the transformation chain.
     *
     * @param array $params Transformation parameters
     * @param array $imageSize Size of the image
     * @return int|array Array containing `width` and `height` or one of the constants defined in
     *                   this interface
     */
    public function getMinimumInputSize(array $params, array $imageSize);

    /**
     * Adjust the parameters for this transformation, in the event that the size of the
     * input image has changed, for instance if the `ImageVariations`-listener is in place
     *
     * @param float $ratio Ratio (input image width / original image width)
     * @param array $parameters Transformation parameters
     * @return array Adjusted parameters
     */
    public function adjustParameters($ratio, array $parameters);
}
