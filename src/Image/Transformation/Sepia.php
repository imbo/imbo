<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Image\Transformation;

use Imbo\Exception\TransformationException,
    ImagickException;

/**
 * Sepia transformation
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Image\Transformations
 */
class Sepia extends Transformation {
    /**
     * Extent of the sepia toning
     *
     * @var float
     */
    private $threshold = 80;

    /**
     * {@inheritdoc}
     */
    public function transform(array $params) {
        $threshold = !empty($params['threshold']) ? (float) $params['threshold'] : $this->threshold;

        try {
            $this->imagick->sepiaToneImage($threshold);
            $this->image->hasBeenTransformed(true);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }
}
