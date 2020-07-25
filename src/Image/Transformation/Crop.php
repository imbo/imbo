<?php
namespace Imbo\Image\Transformation;

use Imbo\Exception\TransformationException;
use Imbo\Image\RegionExtractor;
use Imbo\Image\InputSizeConstraint;
use ImagickException;

/**
 * Crop transformation
 */
class Crop extends Transformation implements RegionExtractor, InputSizeConstraint {
    /**
     * X coordinate of the top left corner of the crop
     *
     * @var int
     */
    private $x = 0;

    /**
     * Y coordinate of the top left corner of the crop
     *
     * @var int
     */
    private $y = 0;

    /**
     * {@inheritdoc}
     */
    public function transform(array $params) {
        $region = $this->getExtractedRegion($params, [
            'width'  => $this->image->getWidth(),
            'height' => $this->image->getHeight(),
        ]);

        if (!$region) {
            return;
        }

        try {
            $this->imagick->cropImage(
                $region['width'],
                $region['height'],
                $region['x'],
                $region['y']
            );

            $this->imagick->setImagePage(0, 0, 0, 0);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }

        $size = $this->imagick->getImageGeometry();

        $this->image->setWidth($size['width'])
                    ->setHeight($size['height'])
                    ->setHasBeenTransformed(true);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtractedRegion(array $params, array $imageSize) {
        foreach (['width', 'height'] as $param) {
            if (!isset($params[$param])) {
                throw new TransformationException('Missing required parameter: ' . $param, 400);
            }
        }

        // Fetch the x, y, width and height of the resulting image
        $x = !empty($params['x']) ? (int) $params['x'] : $this->x;
        $y = !empty($params['y']) ? (int) $params['y'] : $this->y;
        $mode = !empty($params['mode']) ? $params['mode'] : null;

        $width = (int) $params['width'];
        $height = (int) $params['height'];

        // Set correct x and/or y values based on the crop mode
        if ($mode === 'center' || $mode === 'center-x') {
            $x = (int) ($imageSize['width'] - $width) / 2;
        }

        if ($mode === 'center' || $mode === 'center-y') {
            $y = (int) ($imageSize['height'] - $height) / 2;
        }

        // Throw exception on X/Y values that are out of bounds
        if ($x + $width > $imageSize['width']) {
            throw new TransformationException(
                'Crop area is out of bounds (`x` + `width` > image width)',
                400
            );
        } else if ($y + $height > $imageSize['height']) {
            throw new TransformationException(
                'Crop area is out of bounds (`y` + `height` > image height)',
                400
            );
        }

        // Return if there is no need for cropping
        if ($imageSize['width'] === $width && $imageSize['height'] === $height) {
            return false;
        }

        return [
            'width' => $width,
            'height' => $height,
            'x' => $x,
            'y' => $y
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimumInputSize(array $params, array $imageSize) {
        return InputSizeConstraint::NO_TRANSFORMATION;
    }

    /**
     * {@inheritdoc}
     */
    public function adjustParameters($ratio, array $parameters) {
        foreach (['x', 'y', 'width', 'height'] as $param) {
            if (isset($parameters[$param])) {
                $parameters[$param] = round($parameters[$param] / $ratio);
            }
        }

        return $parameters;
    }
}
