<?php declare(strict_types=1);

namespace Imbo\Image\Transformation;

use ImagickException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;
use Imbo\Image\InputSizeConstraint;
use Imbo\Image\RegionExtractor;

class Crop extends Transformation implements RegionExtractor, InputSizeConstraint
{
    /**
     * X coordinate of the top left corner of the crop.
     */
    private int $x = 0;

    /**
     * Y coordinate of the top left corner of the crop.
     */
    private int $y = 0;

    public function transform(array $params): void
    {
        $region = $this->getExtractedRegion($params, [
            'width' => $this->image->getWidth(),
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
                $region['y'],
            );

            $this->imagick->setImagePage(0, 0, 0, 0);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), Response::HTTP_BAD_REQUEST, $e);
        }

        $size = $this->imagick->getImageGeometry();

        $this->image->setWidth($size['width'])
                    ->setHeight($size['height'])
                    ->setHasBeenTransformed(true);
    }

    /**
     * @return array{x:int,y:int,width:int,height:int}|false
     *
     * @throws TransformationException
     */
    public function getExtractedRegion(array $params, array $imageSize)
    {
        foreach (['width', 'height'] as $param) {
            if (!isset($params[$param])) {
                throw new TransformationException('Missing required parameter: '.$param, Response::HTTP_BAD_REQUEST);
            }
        }

        // Fetch the x, y, width and height of the resulting image
        $x = !empty($params['x']) ? (int) $params['x'] : $this->x;
        $y = !empty($params['y']) ? (int) $params['y'] : $this->y;
        $mode = !empty($params['mode']) ? $params['mode'] : null;

        $width = (int) $params['width'];
        $height = (int) $params['height'];

        // Set correct x and/or y values based on the crop mode
        if ('center' === $mode || 'center-x' === $mode) {
            $x = (int) (($imageSize['width'] - $width) / 2);
        }

        if ('center' === $mode || 'center-y' === $mode) {
            $y = (int) (($imageSize['height'] - $height) / 2);
        }

        // Throw exception on X/Y values that are out of bounds
        if ($x + $width > $imageSize['width']) {
            throw new TransformationException('Crop area is out of bounds (`x` + `width` > image width)', Response::HTTP_BAD_REQUEST);
        } elseif ($y + $height > $imageSize['height']) {
            throw new TransformationException('Crop area is out of bounds (`y` + `height` > image height)', Response::HTTP_BAD_REQUEST);
        }

        // Return if there is no need for cropping
        if ($imageSize['width'] === $width && $imageSize['height'] === $height) {
            return false;
        }

        return [
            'width' => $width,
            'height' => $height,
            'x' => $x,
            'y' => $y,
        ];
    }

    public function getMinimumInputSize(array $params, array $imageSize): int
    {
        return InputSizeConstraint::NO_TRANSFORMATION;
    }

    public function adjustParameters(float $ratio, array $parameters): array
    {
        foreach (['x', 'y', 'width', 'height'] as $param) {
            if (isset($parameters[$param])) {
                $parameters[$param] = round($parameters[$param] / $ratio);
            }
        }

        return $parameters;
    }
}
