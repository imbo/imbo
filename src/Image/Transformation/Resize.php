<?php
namespace Imbo\Image\Transformation;

use Imbo\Exception\TransformationException,
    Imbo\Image\InputSizeConstraint,
    ImagickException;

/**
 * Resize transformation
 *
 * @package Image\Transformations
 */
class Resize extends Transformation implements InputSizeConstraint {
    /**
     * {@inheritdoc}
     */
    public function transform(array $params) {
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
            throw new TransformationException($e->getMessage(), 400, $e);
        }

        $newSize = $this->imagick->getImageGeometry();

        $this->image->setWidth($newSize['width'])
                    ->setHeight($newSize['height'])
                    ->hasBeenTransformed(true);
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimumInputSize(array $params, array $imageSize) {
        return $this->calculateSize($params, $imageSize) ?: InputSizeConstraint::NO_TRANSFORMATION;
    }

    /**
     * Calculate output size of image
     *
     * @param array $params
     * @param array $imageSize
     * @return array
     */
    protected function calculateSize(array $params, array $imageSize) {
        if (empty($params['width']) && empty($params['height'])) {
            throw new TransformationException(
                'Missing both width and height. You need to specify at least one of them',
                400
            );
        }

        $width = !empty($params['width']) ? (int) $params['width'] : 0;
        $height = !empty($params['height']) ? (int) $params['height'] : 0;

        $originalWidth = $imageSize['width'];
        $originalHeight = $imageSize['height'];

        if ($width === $originalWidth && $height === $originalHeight) {
            // Resize params match the current image size, no need for any resizing
            return;
        }

        // Calculate width or height if not both have been specified
        if (!$height) {
            $height = ceil(($originalHeight / $originalWidth) * $width);
        } else if (!$width) {
            $width = ceil(($originalWidth / $originalHeight) * $height);
        }

        return ['width' => $width, 'height' => $height];
    }
}
