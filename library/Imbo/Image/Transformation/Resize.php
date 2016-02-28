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
 * Resize transformation
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
class Resize extends Transformation implements ListenerInterface {
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'image.transformation.resize' => 'transform',
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

        if (empty($params['width']) && empty($params['height'])) {
            throw new TransformationException('Missing both width and height. You need to specify at least one of them', 400);
        }

        $width = !empty($params['width']) ? (int) $params['width'] : 0;
        $height = !empty($params['height']) ? (int) $params['height'] : 0;

        $originalWidth = $image->getWidth();
        $originalHeight = $image->getHeight();

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

        try {
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
