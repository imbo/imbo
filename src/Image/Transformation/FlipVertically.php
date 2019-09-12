<?php
namespace Imbo\Image\Transformation;

use Imbo\Exception\TransformationException;
use ImagickException;

/**
 * Flip vertically transformation
 *
 * @package Image\Transformations
 */
class FlipVertically extends Transformation {
    /**
     * {@inheritdoc}
     */
    public function transform(array $params) {
        try {
            $this->imagick->flipImage();
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }

        $this->image->hasBeenTransformed(true);
    }
}
