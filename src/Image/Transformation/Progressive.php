<?php declare(strict_types=1);

namespace Imbo\Image\Transformation;

use Imagick;
use ImagickException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;

/**
 * Progressive image transformation.
 */
class Progressive extends Transformation
{
    public function transform(array $params)
    {
        try {
            $this->imagick->setInterlaceScheme(Imagick::INTERLACE_PLANE);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), Response::HTTP_BAD_REQUEST, $e);
        }

        $this->image->setHasBeenTransformed(true);
    }
}
