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
 * Crop transformation
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
class Crop extends Transformation implements ListenerInterface {
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
    public static function getSubscribedEvents() {
        return [
            'image.transformation.crop' => 'transform',
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
        $imageWidth = $image->getWidth();
        $imageHeight = $image->getHeight();

        // Set correct x and/or y values based on the crop mode
        if ($mode === 'center' || $mode === 'center-x') {
            $x = (int) ($imageWidth - $width) / 2;
        }

        if ($mode === 'center' || $mode === 'center-y') {
            $y = (int) ($imageHeight - $height) / 2;
        }

        // Throw exception on X/Y values that are out of bounds
        if ($x + $width > $imageWidth) {
            throw new TransformationException('Crop area is out of bounds (`x` + `width` > image width)', 400);
        } else if ($y + $height > $imageHeight) {
            throw new TransformationException('Crop area is out of bounds (`y` + `height` > image height)', 400);
        }

        // Return if there is no need for cropping
        if (
            $x === 0 && $y === 0 &&
            $imageWidth <= $width &&
            $imageHeight <= $height
        ) {
            return;
        }

        try {
            $this->imagick->cropImage($width, $height, $x, $y);
            $this->imagick->setImagePage(0, 0, 0, 0);
            $size = $this->imagick->getImageGeometry();

            $image->setWidth($size['width'])
                  ->setHeight($size['height'])
                  ->hasBeenTransformed(true);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }
}
