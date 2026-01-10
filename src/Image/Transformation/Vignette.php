<?php declare(strict_types=1);

namespace Imbo\Image\Transformation;

use Imagick;
use ImagickException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;

/**
 * Vignette transformation.
 */
class Vignette extends Transformation
{
    public function transform(array $params)
    {
        $inner = $this->formatColor(isset($params['inner']) ? $params['inner'] : 'none');
        $outer = $this->formatColor(isset($params['outer']) ? $params['outer'] : '000');
        $scale = (float) max(isset($params['scale']) ? $params['scale'] : 1.5, 1);

        $image = $this->image;
        $width = $image->getWidth();
        $height = $image->getHeight();

        $scaleX = floor($width * $scale);
        $scaleY = floor($height * $scale);

        $vignette = new Imagick();
        $vignette->newPseudoImage((int) $scaleX, (int) $scaleY, 'radial-gradient:'.$inner.'-'.$outer);
        $vignette->cropImage(
            $width,
            $height,
            (int) floor(($scaleX - $width) / 2),
            (int) floor(($scaleY - $height) / 2),
        );

        try {
            $this->imagick->compositeImage($vignette, Imagick::COMPOSITE_MULTIPLY, 0, 0);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), Response::HTTP_BAD_REQUEST, $e);
        }

        $image->setHasBeenTransformed(true);
    }
}
