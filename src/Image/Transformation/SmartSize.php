<?php declare(strict_types=1);

namespace Imbo\Image\Transformation;

use ImagickException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;

use function in_array;

/**
 * SmartSize transformation.
 */
class SmartSize extends Transformation
{
    /**
     * Holds cached metadata for this image.
     */
    private ?array $metadata = null;

    public function transform(array $params): void
    {
        $params = $this->validateParameters($params);

        $this->event->getResponse()->headers->set('X-Imbo-POIs-Used', $params['poi'] ? '1' : '0');

        if (!$params['poi']) {
            $this->simpleCrop($params['width'], $params['height']);

            return;
        }

        $crop = $this->calculateCrop($params, [
            'width' => $this->image->getWidth(),
            'height' => $this->image->getHeight(),
        ]);

        try {
            $this->imagick->cropImage($crop['width'], $crop['height'], $crop['x'], $crop['y']);
            $this->imagick->setImagePage(0, 0, 0, 0);
            $this->imagick->thumbnailImage($params['width'], $params['height']);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), Response::HTTP_BAD_REQUEST, $e);
        }

        $this->image->setWidth($params['width'])
                    ->setHeight($params['height'])
                    ->setHasBeenTransformed(true);
    }

    /**
     * Calculate the coordinates and size of the crop area.
     *
     * @return array Crop data
     */
    private function calculateCrop(array $parameters, array $imageSize)
    {
        $focalX = $parameters['poi'][0];
        $focalY = $parameters['poi'][1];

        $sourceWidth = $imageSize['width'];
        $sourceHeight = $imageSize['height'];
        $sourceRatio = $sourceWidth / $sourceHeight;

        $targetWidth = $parameters['width'];
        $targetHeight = $parameters['height'];
        $targetRatio = $targetWidth / $targetHeight;

        $growFactor = $this->getGrowFactor($parameters['closeness']);
        $sourcePortionThreshold = $this->getSourcePercentageThreshold($parameters['closeness']);

        if ($sourceRatio >= $targetRatio) {
            // Image is wider than needed, crop from the sides
            $cropWidth = (int) ceil(
                $targetRatio * max(
                    min($sourceHeight, $targetHeight * $growFactor),
                    $sourceHeight * $sourcePortionThreshold,
                ),
            );
            $cropHeight = (int) floor($cropWidth / $targetRatio);
        } else {
            // Image is taller than needed, crop from the top/bottom
            $cropHeight = (int) ceil(
                max(
                    min($sourceWidth, $targetWidth * $growFactor),
                    $sourceWidth * $sourcePortionThreshold,
                ) / $targetRatio,
            );
            $cropWidth = (int) floor($cropHeight * $targetRatio);
        }

        $cropTop = (int) ($focalY - floor($cropHeight / 2));
        $cropLeft = (int) ($focalX - floor($cropWidth / 2));

        // Make sure that we're not cropping outside the image on the x axis
        if ($cropLeft < 0) {
            $cropLeft = 0;
        } elseif ($cropLeft + $cropWidth > $sourceWidth) {
            $cropLeft = $sourceWidth - $cropWidth;
        }

        // Make sure that we're not cropping outside the image on the y axis
        if ($cropTop < 0) {
            $cropTop = 0;
        } elseif ($cropTop + $cropHeight > $sourceHeight) {
            $cropTop = $sourceHeight - $cropHeight;
        }

        return [
            'width' => $cropWidth,
            'height' => $cropHeight,
            'x' => $cropLeft,
            'y' => $cropTop,
        ];
    }

    /**
     * Fetch POI from metadata for the image.
     */
    private function getPoiFromMetadata(): ?array
    {
        if (null === $this->metadata) {
            $metadata = $this->event->getDatabase()->getMetadata(
                $this->image->getUser(),
                $this->image->getImageIdentifier(),
            );

            $poi = isset($metadata['poi'][0]) ? $metadata['poi'][0] : false;

            // Fetch POI from metadata. Array used if we want to expand with multiple POIs in the future
            if ($poi && isset($poi['cx']) && isset($poi['cy'])) {
                $this->metadata = [
                    (int) $poi['cx'],
                    (int) $poi['cy'],
                ];
            } elseif (
                $poi
                && isset($poi['x']) && isset($poi['y'])
                && isset($poi['width']) && isset($poi['height'])
            ) {
                $this->metadata = [
                    (int) $poi['x'] + ($poi['width'] / 2),
                    (int) $poi['y'] + ($poi['height'] / 2),
                ];
            } else {
                $this->metadata = null;
            }
        }

        return $this->metadata;
    }

    /**
     * Get the threshold value that specifies the portion of the original width/height that
     * the crop area should never go below.
     *
     * This is important in order to avoid using a very small portion of a large image.
     */
    private function getSourcePercentageThreshold(string $closeness): float
    {
        switch ($closeness) {
            case 'close':
                return 0.3;

            case 'wide':
                return 0.66;

            case 'full':
                return 1;

            default:
                return 0.5;
        }
    }

    /**
     * Get the factor by which the crop area is grown in order to include stuff around
     * the POI. The larger the factor, the wider the crop.
     */
    private function getGrowFactor(string $closeness): float
    {
        switch ($closeness) {
            case 'close':
                return 1;

            case 'wide':
                return 1.6;

            case 'full':
                return 2;

            default:
                return 1.25;
        }
    }

    /**
     * Perform a simple crop/resize operation on the image.
     */
    private function simpleCrop(int $width, int $height): void
    {
        $sourceRatio = $this->image->getWidth() / $this->image->getHeight();
        $cropRatio = $width / $height;

        $params = [];

        if ($cropRatio > $sourceRatio) {
            $params['width'] = $width;
        } else {
            $params['height'] = $height;
        }

        $transformationManager = $this->event->getTransformationManager();

        $maxSize = $transformationManager->getTransformation('maxSize');
        $maxSize->setImage($this->image)->transform($params);

        $crop = $transformationManager->getTransformation('crop');
        $crop->setImage($this->image)->transform([
            'width' => $width,
            'height' => $height,
            'mode' => 'center',
        ]);
    }

    /**
     * Validate parameters and return a normalized parameter array.
     *
     * @throws TransformationException Thrown on invalid or missing parameters
     */
    private function validateParameters(array $params): array
    {
        if (empty($params['width']) || empty($params['height'])) {
            throw new TransformationException('Both width and height needs to be specified', Response::HTTP_BAD_REQUEST);
        }

        $params['width'] = (int) $params['width'];
        $params['height'] = (int) $params['height'];

        // Get POI from transformation params
        $poi = empty($params['poi']) ? null : explode(',', $params['poi']);

        // Check if we have the POI in metadata
        if (!$poi) {
            $metadataPoi = $this->getPoiFromMetadata();

            if (null !== $metadataPoi) {
                $poi = $metadataPoi;
            }
        }

        if ($poi) {
            if (!isset($poi[0]) || !isset($poi[1])) {
                throw new TransformationException('Invalid POI format, expected format `<x>,<y>`', Response::HTTP_BAD_REQUEST);
            }

            if (!empty($params['crop']) && false === in_array($params['crop'], ['close', 'medium', 'wide', 'full'])) {
                throw new TransformationException('Invalid crop value. Valid values are: close,medium,wide,full', Response::HTTP_BAD_REQUEST);
            }
        }

        $params['closeness'] = (isset($params['crop']) ? $params['crop'] : 'medium');
        $params['poi'] = $poi;

        return $params;
    }
}
