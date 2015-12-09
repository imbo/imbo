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
 * MaxSize transformation
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
class MaxSize extends Transformation implements ListenerInterface {
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'image.transformation.maxsize' => 'transform',
        ];
    }

    /**
     * Transform the image
     *
     * @param EventInterface $event The event instance
     */
    public function transform(EventInterface $event) {
        $image = $event->getArgument('image');
        $params = $event->getArgument('params');

        $maxWidth = !empty($params['width']) ? (int) $params['width'] : 0;
        $maxHeight = !empty($params['height']) ? (int) $params['height'] : 0;

        try {
            $sourceWidth  = $image->getWidth();
            $sourceHeight = $image->getHeight();

            $width  = $maxWidth  ?: $sourceWidth;
            $height = $maxHeight ?: $sourceHeight;

            // Figure out original ratio
            $ratio = $sourceWidth / $sourceHeight;

            // Is the original image larger than the max-parameters?
            if (($sourceWidth > $width) || ($sourceHeight > $height)) {
                if (($width / $height) > $ratio) {
                    $width  = round($height * $ratio);
                } else {
                    $height = round($width / $ratio);
                }
            } else {
                // Original image is smaller than the max-parameters, don't transform
                return;
            }

            $this->imagick->setOption('jpeg:size', $width . 'x' . $height);
            $this->imagick->thumbnailImage($width, $height);

            $size = $this->imagick->getImageGeometry();

            $image->setWidth($size['width'])
                  ->setHeight($size['height'])
                  ->hasBeenTransformed(true);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }
}
