<?php
namespace Imbo\Image\Transformation;

use Imbo\Exception\TransformationException;
use ImagickException;

/**
 * Sepia transformation
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
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }

        $this->image->hasBeenTransformed(true);
    }
}
