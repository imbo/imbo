<?php declare(strict_types=1);

namespace Imbo\Image\Transformation;

use ImagickException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;
use Imbo\Image\InputSizeConstraint;

class MaxSize extends Transformation implements InputSizeConstraint
{
    public function transform(array $params): void
    {
        $newSize = $this->calculateSize($params, [
            'width' => $this->image->getWidth(),
            'height' => $this->image->getHeight(),
        ]);

        // No need to transform? Fall back
        if (!$newSize) {
            return;
        }

        try {
            $this->imagick->thumbnailImage($newSize['width'], $newSize['height']);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), Response::HTTP_BAD_REQUEST, $e);
        }

        $size = $this->imagick->getImageGeometry();

        $this->image->setWidth($size['width'])
                    ->setHeight($size['height'])
                    ->setHasBeenTransformed(true);
    }

    public function getMinimumInputSize(array $params, array $imageSize)
    {
        return $this->calculateSize($params, $imageSize) ?: InputSizeConstraint::NO_TRANSFORMATION;
    }

    /**
     * Calculate the output size based on the specified parameters.
     *
     * @return ?array{width:int,height:int}
     */
    protected function calculateSize(array $params, array $imageSize): ?array
    {
        $maxWidth = !empty($params['width']) ? (int) $params['width'] : 0;
        $maxHeight = !empty($params['height']) ? (int) $params['height'] : 0;

        $sourceWidth = $imageSize['width'];
        $sourceHeight = $imageSize['height'];

        $width = $maxWidth ?: $sourceWidth;
        $height = $maxHeight ?: $sourceHeight;

        // Figure out original ratio
        $ratio = $sourceWidth / $sourceHeight;

        if (($width / $height) > $ratio) {
            $width = round($height * $ratio);
        } else {
            $height = round($width / $ratio);
        }

        // Is the original image smaller than the specified parameters?
        if ($sourceWidth <= $width && $sourceHeight <= $height) {
            // Original image is smaller than the max-parameters, don't transform
            return null;
        }

        return ['width' => (int) $width, 'height' => (int) $height];
    }
}
