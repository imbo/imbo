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

        if (empty($params['width']) || empty($params['height'])) {
            throw new TransformationException('Both width and height needs to be specified', 400);
        }

        $poi = empty($params['poi']) ? null : explode(',', $params['poi']);

        if (!$poi) {
            throw new TransformationException('A point-of-interest x,y needs to be specified', 400);
        }

        $focalX = $poi[0];
        $focalY = $poi[1];

        $sourceWidth  = $image->getWidth();
        $sourceHeight = $image->getHeight();
        $sourceRatio  = $sourceWidth / $sourceHeight;

        $targetWidth  = $params['width'];
        $targetHeight = $params['height'];
        $targetRatio  = $targetWidth / $targetHeight;

        $cropWidth;
        $cropHeight;
        $cropLeft;
        $cropTop;

        if ($sourceRatio >= $targetRatio) {
            // Image is wider than needed, crop from the sides
            $cropHeight = $sourceHeight;
            $cropWidth = (int) ceil($targetRatio * $sourceHeight);
            $cropTop = 0;
            $cropLeft = (int) ($focalX - floor($cropWidth / 2));

            // Make sure that we're not cropping outside the image
            if ($cropLeft < 0) {
                $cropLeft = 0;
            } else if ($cropLeft + $cropWidth > $sourceWidth) {
                $cropLeft = $sourceWidth - $cropWidth;
            }
        } else {
            // Image is taller than needed, crop from the top/bottom
            $cropWidth = $sourceWidth;
            $cropHeight = (int) ceil($sourceWidth / $targetRatio);
            $cropLeft = 0;
            $cropTop = (int) ($focalY - floor($cropHeight / 2));

            // Make sure that we're not cropping outside the image
            if ($cropTop < 0) {
                $cropTop = 0;
            } else if ($cropTop + $cropWidth > $sourceHeight) {
                $cropTop = $sourceHeight - $cropHeight;
            }
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
