<?php
namespace Imbo\Image\Transformation;

use Imbo\Exception\TransformationException,
    Imbo\Image\InputSizeConstraint,
    ImagickException;

/**
 * MaxSize transformation
 *
 * @package Image\Transformations
 */
class MaxSize extends Transformation implements InputSizeConstraint {
    /**
     * {@inheritdoc}
     */
    public function transform(array $params) {
        $newSize = $this->calculateSize($params, [
            'width'  => $this->image->getWidth(),
            'height' => $this->image->getHeight(),
        ]);

        // No need to transform? Fall back
        if (!$newSize) {
            return;
        }

        try {
            $this->imagick->thumbnailImage($newSize['width'], $newSize['height']);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }

        $size = $this->imagick->getImageGeometry();

        $this->image->setWidth($size['width'])
                    ->setHeight($size['height'])
                    ->hasBeenTransformed(true);
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimumInputSize(array $params, array $imageSize) {
        return $this->calculateSize($params, $imageSize) ?: InputSizeConstraint::NO_TRANSFORMATION;
    }

    /**
     * Calculate the output size based on the specified parameters
     *
     * @param array $params
     * @param array $imageSize
     * @return array|boolean
     */
    protected function calculateSize(array $params, array $imageSize) {
        $maxWidth = !empty($params['width']) ? (int) $params['width'] : 0;
        $maxHeight = !empty($params['height']) ? (int) $params['height'] : 0;

        $sourceWidth  = $imageSize['width'];
        $sourceHeight = $imageSize['height'];

        $width  = $maxWidth  ?: $sourceWidth;
        $height = $maxHeight ?: $sourceHeight;

        // Figure out original ratio
        $ratio = $sourceWidth / $sourceHeight;

        if (($width / $height) > $ratio) {
            $width  = round($height * $ratio);
        } else {
            $height = round($width / $ratio);
        }

        // Is the original image smaller than the specified parameters?
        if ($sourceWidth <= $width && $sourceHeight <= $height) {
            // Original image is smaller than the max-parameters, don't transform
            return;
        }

        return ['width' => $width, 'height' => $height];
    }
}
