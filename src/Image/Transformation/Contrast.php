<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use ImagickException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;

/**
 * Contrast transformation
 */
class Contrast extends Transformation
{
    public function transform(array $params)
    {
        $alpha = isset($params['sharpen']) ? (float) $params['sharpen'] : 1;
        $alpha = isset($params['alpha']) ? (float) $params['alpha'] : $alpha;
        $beta = isset($params['beta']) ? (float) $params['beta'] : 0.5;
        $sharpen = $alpha > 0;

        if ($alpha == 0) {
            return;
        }

        $beta *= $this->getQuantumRange();

        try {
            $this->imagick->sigmoidalContrastImage($sharpen, abs($alpha), $beta);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), Response::HTTP_BAD_REQUEST, $e);
        }

        $this->image->setHasBeenTransformed(true);
    }
}
