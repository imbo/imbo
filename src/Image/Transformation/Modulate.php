<?php declare(strict_types=1);

namespace Imbo\Image\Transformation;

use ImagickException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;

/**
 * Modulate transformation.
 */
class Modulate extends Transformation
{
    public function transform(array $params)
    {
        $brightness = isset($params['b']) ? (int) $params['b'] : 100;
        $saturation = isset($params['s']) ? (int) $params['s'] : 100;
        $hue = isset($params['h']) ? (int) $params['h'] : 100;

        try {
            $this->imagick->modulateImage($brightness, $saturation, $hue);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), Response::HTTP_BAD_REQUEST, $e);
        }

        $this->image->setHasBeenTransformed(true);
    }
}
