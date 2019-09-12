<?php
namespace Imbo\Image\Transformation;

use Imbo\Exception\TransformationException,
    ImagickException;

/**
 * Sharpen transformation
 *
 * @package Image\Transformations
 */
class Sharpen extends Transformation {
    /**
     * {@inheritdoc}
     */
    public function transform(array $params) {
        $preset = isset($params['preset']) ? $params['preset'] : null;

        switch ($preset) {
            case 'moderate':
                $radius = 2;
                $sigma = 1;
                $gain = 2;
                $threshold = .05;
                break;

            case 'strong':
                $radius = 2;
                $sigma = 1;
                $gain = 3;
                $threshold = .025;
                break;

            case 'extreme':
                $radius = 2;
                $sigma = 1;
                $gain = 4;
                $threshold = 0;
                break;

            case 'light':
            default:
                // Default values (with only adding ?t[]=sharpen)
                $radius = 2;
                $sigma = 1;
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
            throw new TransformationException($e->getMessage(), 400, $e);
        }

        $this->image->hasBeenTransformed(true);
    }
}
