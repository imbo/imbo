<?php declare(strict_types=1);

namespace Imbo\Image\Transformation;

use ImagickException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;

use function in_array;

/**
 * Blur transformation.
 */
class Blur extends Transformation
{
    public function transform(array $params): void
    {
        $type = isset($params['type']) ? $params['type'] : 'gaussian';

        $blurTypes = ['gaussian', 'adaptive', 'motion', 'radial', 'rotational'];

        if (!in_array($type, $blurTypes)) {
            throw new TransformationException('Unknown blur type: '.$type, Response::HTTP_BAD_REQUEST);
        }

        switch ($type) {
            case 'motion':
                $this->motionBlur($params);
                break;

            case 'rotational':
            case 'radial':
                $this->rotationalBlur($params);
                break;

            case 'adaptive':
                $this->blur($params, true);
                break;

            default:
                $this->blur($params);
                break;
        }
    }

    /**
     * Check that all params are present.
     *
     * @param array $params   Transformation parameter list
     * @param array $required Array with required parameters
     *
     * @throws TransformationException
     */
    private function checkRequiredParams(array $params, array $required): void
    {
        foreach ($required as $param) {
            if (!isset($params[$param])) {
                throw new TransformationException('Missing required parameter: '.$param, Response::HTTP_BAD_REQUEST);
            }
        }
    }

    /**
     * Add Gaussian or adaptive blur to the image.
     *
     * @param array $params   The transformation parameters
     * @param bool  $adaptive Perform adaptive blur or not
     */
    private function blur(array $params, $adaptive = false): void
    {
        $this->checkRequiredParams($params, ['radius', 'sigma']);

        $radius = (float) $params['radius'];
        $sigma = (float) $params['sigma'];

        try {
            if ($adaptive) {
                $this->imagick->adaptiveBlurImage($radius, $sigma);
            } else {
                $this->imagick->gaussianBlurImage($radius, $sigma);
            }
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), Response::HTTP_BAD_REQUEST, $e);
        }

        $this->image->setHasBeenTransformed(true);
    }

    /**
     * Add motion blur to the image.
     *
     * @param array $params The transformation parameters
     */
    private function motionBlur(array $params): void
    {
        $this->checkRequiredParams($params, ['radius', 'sigma', 'angle']);

        $radius = (float) $params['radius'];
        $sigma = (float) $params['sigma'];
        $angle = (float) $params['angle'];

        try {
            $this->imagick->motionBlurImage($radius, $sigma, $angle);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), Response::HTTP_BAD_REQUEST, $e);
        }

        $this->image->setHasBeenTransformed(true);
    }

    /**
     * Add rotational blur to the image.
     *
     * @param array $params The transformation parameters
     */
    private function rotationalBlur(array $params): void
    {
        $this->checkRequiredParams($params, ['angle']);

        $angle = (float) $params['angle'];

        try {
            $this->imagick->rotationalBlurImage($angle);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), Response::HTTP_BAD_REQUEST, $e);
        }

        $this->image->setHasBeenTransformed(true);
    }
}
