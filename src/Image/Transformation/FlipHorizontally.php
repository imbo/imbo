<?php
namespace Imbo\Image\Transformation;

use Imbo\Exception\TransformationException;
use ImagickException;

/**
 * Flip horizontally transformation
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
