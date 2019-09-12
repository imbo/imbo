<?php
namespace Imbo\Image\Transformation;

use Imbo\Exception\TransformationException,
    ImagickException;

/**
 * Modulate transformation
 *
 * @package Image\Transformations
 */
class Modulate extends Transformation {
    /**
     * {@inheritdoc}
     */
    public function transform(array $params) {
        $brightness = isset($params['b']) ? (int) $params['b'] : 100;
        $saturation = isset($params['s']) ? (int) $params['s'] : 100;
        $hue = isset($params['h']) ? (int) $params['h'] : 100;

        try {
            $this->imagick->modulateImage($brightness, $saturation, $hue);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }

        $this->image->hasBeenTransformed(true);
    }
}
