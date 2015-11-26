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
 * Input size aware interface - transformations that implement this interface
 * can let Imbo know the minimum size of the input image that it can receive,
 * given a set of parameters.
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Image
 */
interface InputSizeAware {
    public function getMinimumInputSize(array $params);
}
