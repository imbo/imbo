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

use Imbo\Image\Image,
    Imbo\Exception\TransformationException,
    ImagickException;

/**
 * Sepia transformation
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Image\Transformations
 */
class Sepia extends Transformation implements TransformationInterface {
    /**
     * Extent of the sepia toning
     *
     * @var float
     */
    private $threshold;

    /**
     * Class constructor
     *
     * @param array $params Parameters for this transformation
     */
    public function __construct(array $params) {
        $this->threshold = isset($params['threshold']) ? (float) $params['threshold'] : 80;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToImage(Image $image) {
        try {
            $imagick = $this->getImagick();
            $imagick->readImageBlob($image->getBlob());

            $imagick->sepiaToneImage($this->threshold);

            $image->setBlob($imagick->getImageBlob());
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }
}
