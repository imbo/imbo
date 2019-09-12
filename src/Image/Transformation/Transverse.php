<?php
namespace Imbo\Image\Transformation;

use Imbo\Exception\TransformationException,
    ImagickException;

/**
 * Transverse transformation
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
class Transverse extends Transformation {
    /**
     * {@inheritdoc}
     */
    public function transform(array $params) {
        try {
            $this->imagick->transverseImage();
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }

        $this->image->hasBeenTransformed(true);
    }
}
