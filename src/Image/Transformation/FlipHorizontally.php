<?php
namespace Imbo\Image\Transformation;

use Imbo\Exception\TransformationException,
    ImagickException;

/**
 * Flip horizontally transformation
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
class FlipHorizontally extends Transformation {
    /**
     * {@inheritdoc}
     */
    public function transform(array $params) {
        try {
            $this->imagick->flopImage();
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }

        $this->image->hasBeenTransformed(true);
    }
}
