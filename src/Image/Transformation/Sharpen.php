<?php declare(strict_types=1);

namespace Imbo\Image\Transformation;

use ImagickException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;

/**
 * Sharpen transformation.
 */
class Sharpen extends Transformation
{
    public function transform(array $params)
    {
        $preset = isset($params['preset']) ? $params['preset'] : null;
        $radius = 2;
        $sigma = 1;

        switch ($preset) {
            case 'moderate':
                $gain = 2;
                $threshold = .05;
                break;

            case 'strong':
                $gain = 3;
                $threshold = .025;
                break;

            case 'extreme':
                $gain = 4;
                $threshold = 0;
                break;

            case 'light':
            default:
                // Default values (with only adding ?t[]=sharpen)
                $gain = 1;
                $threshold = .05;
        }

        if (isset($params['radius'])) {
            $radius = (float) $params['radius'];
        }

        if (isset($params['sigma'])) {
            $sigma = (float) $params['sigma'];
        }

        if (isset($params['gain'])) {
            $gain = (float) $params['gain'];
        }

        if (isset($params['threshold'])) {
            $threshold = (float) $params['threshold'];
        }

        try {
            $this->imagick->unsharpMaskImage($radius, $sigma, $gain, $threshold);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), Response::HTTP_BAD_REQUEST, $e);
        }

        $this->image->setHasBeenTransformed(true);
    }
}
