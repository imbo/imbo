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
    Imbo\EventListener\ListenerInterface,
    Imbo\EventManager\EventInterface,
    ImagickException;

/**
 * SmartSize transformation
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @author Kristoffer Brabrand <kristoffer@brabrand.no>
 * @package Image\Transformations
 */
class SmartSize extends Transformation implements ListenerInterface {
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'image.transformation.smartsize' => 'transform',
        ];
    }

    public function transform(EventInterface $event) {
        $image = $event->getArgument('image');
        $params = $event->getArgument('params');

        // Factor that the target width/height is grown by when cropping. THe lower this factor is
        // set, the closer the crop is
        $growFactor = 1.25;

        // Threshold of the original width/height that the crop area should never go below
        // this is important to make sure that a too small portion of a large image is selected
        $sourcePortionThreshold = 0.5;

        if (empty($params['width']) || empty($params['height'])) {
            throw new TransformationException('Both width and height needs to be specified', 400);
        }

        $poi = empty($params['poi']) ? null : explode(',', $params['poi']);

        if (!$poi) {
            throw new TransformationException('A point-of-interest x,y needs to be specified', 400);
        }

        if (!empty($params['crop']) && array_search($params['crop'], ['close', 'medium', 'wide']) === false) {
            throw new TransformationException('Invalid crop value. Valid values are: close,medium,wide', 400);
        }

        // Crop factor presets
        if (!empty($params['crop'])) {
            switch ($params['crop']) {
                case 'close':
                    $growFactor = 1;
                    $sourcePortionThreshold = 0.3;
                    break;

                case 'wide':
                    $growFactor = 1.6;
                    $sourcePortionThreshold = 0.66;
                    break;
            }
        }

        $focalX = $poi[0];
        $focalY = $poi[1];

        $sourceWidth  = $image->getWidth();
        $sourceHeight = $image->getHeight();
        $sourceRatio  = $sourceWidth / $sourceHeight;

        $targetWidth  = $params['width'];
        $targetHeight = $params['height'];
        $targetRatio  = $targetWidth / $targetHeight;

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

        try {
            $this->imagick->cropImage($cropWidth, $cropHeight, $cropLeft, $cropTop);
            $this->imagick->setImagePage(0, 0, 0, 0);
            $this->resize($image, $targetWidth, $targetHeight);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }

    private function resize($image, $targetWidth, $targetHeight) {
        $this->imagick->setOption('jpeg:size', $targetWidth . 'x' . $targetHeight);
        $this->imagick->thumbnailImage($targetWidth, $targetHeight);

        $image->setWidth($targetWidth)
              ->setHeight($targetHeight)
              ->hasBeenTransformed(true);
    }
}
