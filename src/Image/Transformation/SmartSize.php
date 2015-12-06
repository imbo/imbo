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

use Imbo\Exception\TransformationException,
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
     * {@inheritdoc}
     */
    public function transform(array $params) {
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

        $this->event->getResponse()->headers->set('X-Imbo-POIs-Used', $poi ? 1 : 0);

        // Do a simple crop if don't have a POI
        if (!$poi) {
            return $this->simpleCrop($params['width'], $params['height']);
        }

        if (!isset($poi[0]) || !isset($poi[1])) {
            throw new TransformationException('Invalid POI format, expected format `<x>,<y>`', 400);
        }

        if (!empty($params['crop']) && in_array($params['crop'], ['close', 'medium', 'wide']) === false) {
            throw new TransformationException('Invalid crop value. Valid values are: close,medium,wide', 400);
        }

        $targetWidth = $params['width'];
        $targetHeight = $params['height'];
        $closeness = (isset($params['crop']) ? $params['crop'] : 'medium');

        $crop = $this->calculateCrop([
            'focalX' => $poi[0],
            'focalY' => $poi[1],

            'sourceWidth' => $this->image->getWidth(),
            'sourceHeight' => $this->image->getHeight(),

            'targetWidth' => $targetWidth,
            'targetHeight' => $targetHeight,

            'growFactor' => $this->getGrowFactor($closeness),
            'sourcePortionThreshold' => $this->getSourcePercentageThreshold($closeness)
        ]);

        try {
            $this->imagick->cropImage($crop['width'], $crop['height'], $crop['left'], $crop['top']);
            $this->imagick->setImagePage(0, 0, 0, 0);
            $this->resize($targetWidth, $targetHeight);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }

    /**
     * Calculate the coordinates and size of the crop area
     *
     * @param array $parameters
     * @return array Crop data
     */
    private function calculateCrop($parameters) {
        $focalX = $parameters['focalX'];
        $focalY  = $parameters['focalY'];

        $sourceWidth = $parameters['sourceWidth'];
        $sourceHeight = $parameters['sourceHeight'];
        $sourceRatio  = $sourceWidth / $sourceHeight;

        $targetWidth = $parameters['targetWidth'];
        $targetHeight = $parameters['targetHeight'];
        $targetRatio  = $targetWidth / $targetHeight;

        $growFactor = $parameters['growFactor'];
        $sourcePortionThreshold = $parameters['sourcePortionThreshold'];

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
            'top' => $cropTop,
            'left' => $cropLeft
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
        $metadata = $this->event->getDatabase()->getMetadata(
            $this->image->getUser(),
            $this->image->getImageIdentifier()
        );

        $poi = isset($metadata['poi'][0]) ? $metadata['poi'][0] : false;

        // Fetch POI from metadata. Array used if we want to expand with multiple POIs in the future
        if ($poi && isset($poi['cx']) && isset($poi['cy'])) {
            return [
                (int) $poi['cx'],
                (int) $poi['cy']
            ];
        } else if (
            $poi &&
            isset($poi['x']) && isset($poi['y']) &&
            isset($poi['width']) && isset($poi['height'])
        ) {
            return [
                (int) $poi['x'] + ($poi['width']  / 2),
                (int) $poi['y'] + ($poi['height'] / 2)
            ];
        }

        return false;
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
}
