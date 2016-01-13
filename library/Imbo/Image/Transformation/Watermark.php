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

use Imbo\Exception\StorageException,
    Imbo\Exception\TransformationException,
    Imbo\EventListener\ListenerInterface,
    Imbo\EventManager\EventInterface,
    Imagick,
    ImagickException;

/**
 * Watermark transformation
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Image\Transformations
 */
class Watermark extends Transformation implements ListenerInterface {
    /**
     * Default image identifier to use for watermarks
     *
     * @var string
     */
    private $defaultImage;

    /**
     * X coordinate of watermark relative to position parameters
     *
     * @var int
     */
    private $x = 0;

    /**
     * Y coordinate of watermark relative to position parameters
     *
     * @var int
     */
    private $y = 0;

    /**
     * Position of watermark within original image
     *
     * Supported modes:
     *
     * - "top-left" (default): Places the watermark in the top left corner
     * - "top-right": Places the watermark in the top right corner
     * - "bottom-left": Places the watermark in the bottom left corner
     * - "bottom-right": Places the watermark in the bottom right corner
     * - "center": Places the watermark in the center of the image
     *
     * @var string
     */
    private $position = 'top-left';

    /**
     * Set default image identifier to use if no identifier has been specified
     *
     * @param string $imageIdentifier Image identifier for the default image
     */
    public function setDefaultImage($imageIdentifier) {
        $this->defaultImage = $imageIdentifier;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'image.transformation.watermark' => 'transform',
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

        $width = !empty($params['width']) ? (int) $params['width'] : 0;
        $height = !empty($params['height']) ? (int) $params['height'] : 0;
        $imageIdentifier = !empty($params['img']) ? $params['img'] : $this->defaultImage;
        $position = !empty($params['position']) ? $params['position'] : $this->position;
        $x = !empty($params['x']) ? (int) $params['x'] : $this->x;
        $y = !empty($params['y']) ? (int) $params['y'] : $this->y;
        $opacity = (!empty($params['opacity']) ? (int) $params['opacity'] : 100)/100;

        if (empty($imageIdentifier)) {
            throw new TransformationException(
                'You must specify an image identifier to use for the watermark',
                400
            );
        }

        // Try to load watermark image from storage
        try {
            $watermarkData = $event->getStorage()->getImage(
                $event->getRequest()->getUser(),
                $imageIdentifier
            );

            $watermark = new Imagick();
            $watermark->readImageBlob($watermarkData);
            $watermarkSize = $watermark->getImageGeometry();
            $watermark->setImageOpacity($opacity);
        } catch (StorageException $e) {
            if ($e->getCode() == 404) {
                throw new TransformationException('Watermark image not found', 400);
            }

            throw $e;
        }

        // Should we resize the watermark?
        if ($height || $width) {
            // Calculate width or height if not both have been specified
            if (!$height) {
                $height = ($watermarkSize['height'] / $watermarkSize['width']) * $width;
            } else if (!$width) {
                $width = ($watermarkSize['width'] / $watermarkSize['height']) * $height;
            }

            $watermark->thumbnailImage($width, $height);
        } else {
            $width = $watermarkSize['width'];
            $height = $watermarkSize['height'];
        }

        // Determine placement of the watermark
        if ($position === 'top-right') {
            $x = $image->getWidth() - $width + $x;
        } else if ($position === 'bottom-left') {
            $y = $image->getHeight() - $height + $y;
        } else if ($position === 'bottom-right') {
            $x = $image->getWidth() - $width + $x;
            $y = $image->getHeight() - $height + $y;
        } else if ($position === 'center') {
            $x = ($image->getWidth() / 2) - ($width / 2) + $x;
            $y = ($image->getHeight() / 2) - ($height / 2) + $y;
        }

        // Now make a composite
        try {
            $this->imagick->compositeImage($watermark, Imagick::COMPOSITE_OVER, $x, $y);
            $image->hasBeenTransformed(true);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }
}
