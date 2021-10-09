<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use ImagickException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;
use Imbo\Image\InputSizeConstraint;

/**
 * Resize transformation
 */
class Resize extends Transformation implements InputSizeConstraint
{
    public function transform(array $params)
    {
        $size = $this->calculateSize($params, [
            'width'  => $this->image->getWidth(),
            'height' => $this->image->getHeight(),
        ]);

        // Fall back if there is no need to resize
        if (!$size) {
            return;
        }

        try {
            $this->imagick->thumbnailImage($size['width'], $size['height']);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), Response::HTTP_BAD_REQUEST, $e);
        }

        $newSize = $this->imagick->getImageGeometry();

        $this->image->setWidth($newSize['width'])
                    ->setHeight($newSize['height'])
                    ->setHasBeenTransformed(true);
    }

    public function getMinimumInputSize(array $params, array $imageSize)
    {
        return $this->calculateSize($params, $imageSize) ?: InputSizeConstraint::NO_TRANSFORMATION;
    }

    /**
     * Calculate output size of image
     *
     * @param array $params
     * @param array $imageSize
     * @return ?array{width:int,height:int}
     */
    protected function calculateSize(array $params, array $imageSize): ?array
    {
        if (empty($params['width']) && empty($params['height'])) {
            throw new TransformationException(
                'Missing both width and height. You need to specify at least one of them',
                Response::HTTP_BAD_REQUEST,
            );
        }

        $width = !empty($params['width']) ? (int) $params['width'] : 0;
        $height = !empty($params['height']) ? (int) $params['height'] : 0;

        $originalWidth = $imageSize['width'];
        $originalHeight = $imageSize['height'];

        if ($width === $originalWidth && $height === $originalHeight) {
            // Resize params match the current image size, no need for any resizing
            return null;
        }

        // Calculate width or height if not both have been specified
        if (!$height) {
            $height = ceil(($originalHeight / $originalWidth) * $width);
        } elseif (!$width) {
            $width = ceil(($originalWidth / $originalHeight) * $height);
        }

        return ['width' => (int) $width, 'height' => (int) $height];
    }
}
