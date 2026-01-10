<?php declare(strict_types=1);

namespace Imbo\Image\Transformation;

use ImagickException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;

/**
 * Sepia transformation.
 */
class Sepia extends Transformation
{
    /**
     * Extent of the sepia toning.
     *
     * @var float
     */
    private $threshold = 80;

    public function transform(array $params)
    {
        $threshold = !empty($params['threshold']) ? (float) $params['threshold'] : $this->threshold;

        try {
            $this->imagick->sepiaToneImage($threshold);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), Response::HTTP_BAD_REQUEST, $e);
        }

        $this->image->setHasBeenTransformed(true);
    }
}
