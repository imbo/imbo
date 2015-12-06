<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Image\Transformation;

use Imbo\Image\RegionExtractor,
    Imbo\Image\InputSizeConstraint,
    Imbo\Exception\TransformationException,
    ImagickException;

/**
 * SmartSize transformation
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @author Kristoffer Brabrand <kristoffer@brabrand.no>
 * @package Image\Transformations
 */
class SmartSize extends Transformation {
    /**
     * Holds cached metadata for this image
     *
     * @var array
     */
    private $metadata = null;

    /**
     * {@inheritdoc}
     */
    public function transform(array $params) {
        $params = $this->validateParameters($params);

        $this->event->getResponse()->headers->set('X-Imbo-POIs-Used', $params['poi'] ? 1 : 0);

        if (!$params['poi']) {
            return $this->simpleCrop($params['width'], $params['height']);
        }

        $crop = $this->calculateCrop($params, [
            'width'  => $this->image->getWidth(),
            'height' => $this->image->getHeight(),
        ]);

        try {
            $this->imagick->cropImage($crop['width'], $crop['height'], $crop['x'], $crop['y']);
            $this->imagick->setImagePage(0, 0, 0, 0);
            $this->resize($params['width'], $params['height']);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }

    /**
     * Calculate the coordinates and size of the crop area
     *
     * @param array $parameters
     * @param array $imageSize
     * @return array Crop data
     */
    private function calculateCrop(array $parameters, array $imageSize) {
        $focalX = $parameters['poi'][0];
        $focalY  = $parameters['poi'][1];

        $sourceWidth = $imageSize['width'];
        $sourceHeight = $imageSize['height'];
        $sourceRatio  = $sourceWidth / $sourceHeight;

        $targetWidth = $parameters['width'];
        $targetHeight = $parameters['height'];
        $targetRatio  = $targetWidth / $targetHeight;

        $growFactor = $this->getGrowFactor($parameters['closeness']);
        $sourcePortionThreshold = $this->getSourcePercentageThreshold($parameters['closeness']);

        if ($sourceRatio >= $targetRatio) {
            // Image is wider than needed, crop from the sides
            $cropWidth = (int) ceil(
                $targetRatio * max(
                    min($sourceHeight, $targetHeight * $growFactor),
                    $sourceHeight * $sourcePortionThreshold
                )
            );
            $cropHeight = (int) floor($cropWidth / $targetRatio);
        } else {
            // Image is taller than needed, crop from the top/bottom
            $cropHeight = (int) ceil(
                max(
                    min($sourceWidth, $targetWidth * $growFactor),
                    $sourceWidth * $sourcePortionThreshold
                ) / $targetRatio
            );
            $cropWidth = (int) floor($cropHeight * $targetRatio);
        }

        $cropTop = (int) ($focalY - floor($cropHeight / 2));
        $cropLeft = (int) ($focalX - floor($cropWidth / 2));

        // Make sure that we're not cropping outside the image on the x axis
        if ($cropLeft < 0) {
            $cropLeft = 0;
        } else if ($cropLeft + $cropWidth > $sourceWidth) {
            $cropLeft = $sourceWidth - $cropWidth;
        }

        // Make sure that we're not cropping outside the image on the y axis
        if ($cropTop < 0) {
            $cropTop = 0;
        } else if ($cropTop + $cropHeight > $sourceHeight) {
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
     * Fetch POI from metadata for the image
     *
     * @param EventInterface $event
     * @param Image $image
     * @return array|false Array with x and y coordinate, or false if no POI was found
     */
    private function getPoiFromMetadata() {
        if ($this->metadata === null) {
            $metadata = $this->event->getDatabase()->getMetadata(
                $this->image->getUser(),
                $this->image->getImageIdentifier()
            );

            $poi = isset($metadata['poi'][0]) ? $metadata['poi'][0] : false;

            // Fetch POI from metadata. Array used if we want to expand with multiple POIs in the future
            if ($poi && isset($poi['cx']) && isset($poi['cy'])) {
                $this->metadata = [
                    (int) $poi['cx'],
                    (int) $poi['cy']
                ];
            } else if (
                $poi &&
                isset($poi['x']) && isset($poi['y']) &&
                isset($poi['width']) && isset($poi['height'])
            ) {
                $this->metadata = [
                    (int) $poi['x'] + ($poi['width']  / 2),
                    (int) $poi['y'] + ($poi['height'] / 2)
                ];
            } else {
                $this->metadata = false;
            }
        }

        return $this->metadata;
    }

    /**
      * Get the threshold value that specifies the portion of the original width/height that
      * the crop area should never go below.
      *
      * This is important in order to avoid using a very small portion of a large image.
      *
      * @param $closeness Closeness of crop
     */
    private function getSourcePercentageThreshold($closeness) {
        switch ($closeness) {
            case 'close':
                return 0.3;

            case 'wide':
                return 0.66;

            default:
                return 0.5;
        }
    }

    /**
      * Get the factor by which the crop area is grown in order to include stuff around
      * the POI. The larger the factor, the wider the crop.
      *
      * @param $closeness Closeness of crop
     */
    private function getGrowFactor($closeness) {
        switch ($closeness) {
            case 'close':
                return 1;

            case 'wide':
                return 1.6;

            default:
                return 1.25;
        }
    }

    /**
     * Resize the image
     *
     * @param int $targetWidth The resize target width
     * @param int $tartHeight The resize target height
     */
    private function resize($targetWidth, $targetHeight) {
        $this->imagick->thumbnailImage($targetWidth, $targetHeight);

        $this->image
             ->setWidth($targetWidth)
             ->setHeight($targetHeight)
             ->hasBeenTransformed(true);
    }

    /**
     * Perform a simple crop/resize operation on the image
     *
     * @param int $width
     * @param int $height
     */
    private function simpleCrop($width, $height) {
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
            'mode' => 'center'
        ]);
    }

    /**
     * Validate parameters and return a normalized parameter array
     *
     * @param array $params
     * @return array
     * @throws TransformationException Thrown on invalid or missing parameters
     */
    private function validateParameters(array $params) {
        if (empty($params['width']) || empty($params['height'])) {
            throw new TransformationException('Both width and height needs to be specified', 400);
        }

        // Get POI from transformation params
        $poi = empty($params['poi']) ? null : explode(',', $params['poi']);

        // Check if we have the POI in metadata
        if (!$poi) {
            $metadataPoi = $this->getPoiFromMetadata();

            if ($metadataPoi) {
                $poi = $metadataPoi;
            }
        }

        if ($poi) {
            if (!isset($poi[0]) || !isset($poi[1])) {
                throw new TransformationException('Invalid POI format, expected format `<x>,<y>`', 400);
            }

            if (!empty($params['crop']) && in_array($params['crop'], ['close', 'medium', 'wide']) === false) {
                throw new TransformationException('Invalid crop value. Valid values are: close,medium,wide', 400);
            }
        }

        $params['closeness'] = (isset($params['crop']) ? $params['crop'] : 'medium');
        $params['poi'] = $poi;

        return $params;
    }
}
