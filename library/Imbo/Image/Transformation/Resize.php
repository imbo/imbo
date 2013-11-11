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

use Imbo\Model\Image,
    Imbo\Exception\TransformationException,
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
        return array(
            'image.transformation.resize' => 'transform',
        );
    }

    /**
     */
    public function transform(EventInterface $event) {
        $image = $event->getArgument('image');
        $params = $event->getArgument('params');

        if (empty($params['width']) && empty($params['height'])) {
            throw new TransformationException('Missing both width and height. You need to specify at least one of them', 400);
        }

        $width = !empty($params['width']) ? (int) $params['width'] : 0;
        $height = !empty($params['height']) ? (int) $params['height'] : 0;

        // Calculate width or height if not both have been specified
        if (!$height) {
            $height = ($image->getHeight() / $image->getWidth()) * $width;
        } else if (!$width) {
            $width = ($image->getWidth() / $image->getHeight()) * $height;
        }

        try {

            $imagick = $this->getImagick();
            $imagick->setOption('jpeg:size', $width . 'x' . $height);
            $imagick->readImageBlob($image->getBlob());
            $imagick->thumbnailImage($width, $height);

            $size = $imagick->getImageGeometry();

            $image->setBlob($imagick->getImageBlob())
                  ->setWidth($size['width'])
                  ->setHeight($size['height']);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }
}
