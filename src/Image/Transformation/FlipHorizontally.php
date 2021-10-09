<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use ImagickException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;

/**
 * Flip horizontally transformation
 */
class FlipHorizontally extends Transformation
{
    /**
     * {@inheritdoc}
     */
    public function transform(array $params)
    {
        try {
            $this->imagick->flopImage();
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), Response::HTTP_BAD_REQUEST, $e);
        }

        $this->image->setHasBeenTransformed(true);
    }
}
