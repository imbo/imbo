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
    Imbo\Helpers\Imagick as ImagickHelper,
    Imbo\Image\InputSizeConstraint,
    Imagick,
    ImagickException;

/**
 * Watermark transformation
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Image\Transformations
 */
class Watermark extends Transformation implements InputSizeConstraint {
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
     * - "bottom": Places the watermark in the bottom center
     * - "top": Places the watermark in the top center
     * - "right": Places the watermark in the right center
     * - "left": Places the watermark in the left center
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
    public function transform(array $params) {
        $width = !empty($params['width']) ? (int) $params['width'] : 0;
        $height = !empty($params['height']) ? (int) $params['height'] : 0;
        $imageIdentifier = !empty($params['img']) ? $params['img'] : $this->defaultImage;
        $position = !empty($params['position']) ? $params['position'] : $this->position;
        $x = !empty($params['x']) ? (int) $params['x'] : $this->x;
        $y = !empty($params['y']) ? (int) $params['y'] : $this->y;
        $opacity = (!empty($params['opacity']) ? (int) $params['opacity'] : 100)/100;
        $image = $this->image;

        if (empty($imageIdentifier)) {
            throw new TransformationException(
                'You must specify an image identifier to use for the watermark',
                400
            );
        }

        // Try to load watermark image from storage
        try {
            $watermarkData = $this->event->getStorage()->getImage(
                $this->event->getRequest()->getUser(),
                $imageIdentifier
            );

        } catch (StorageException $e) {
            if ($e->getCode() == 404) {
                throw new TransformationException('Watermark image not found', 400);
            }

            throw $e;
        }

        $watermark = new Imagick();
        $watermark->readImageBlob($watermarkData);
        $watermarkSize = $watermark->getImageGeometry();

        // we can't use ->setImageOpacity here as it also affects the alpha channel, generating a "ghost" area
        // around any masked area. By using evaluateImage we multiply existing alpha values instead, allowing us
        // to retain any existing transparency.
        if ($opacity < 1) {
            // if there's no alpha channel already, we have to enable it before calculating transparency
            if (!$watermark->getImageAlphaChannel()) {
                try {
                    $watermark->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
                } catch (ImagickException $e) {
                    // there's a bug in Imagemagick < 6.8.0-4 which throws an exception even if the value was set
                    // https://imagemagick.org/discourse-server/viewtopic.php?t=22152
                    $version = ImagickHelper::getInstalledVersion();

                    if (version_compare($version, '6.8.0-4', '>=')) {
                        // rethrow exception if we're on 6.8.0-4 or newer.
                        throw $e;
                    }
                }
            }

            $watermark->evaluateImage(Imagick::EVALUATE_MULTIPLY, $opacity, Imagick::CHANNEL_ALPHA);
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
        } else if ($position === 'bottom') {
            $x = ($image->getWidth() / 2) - ($width / 2) + $x;
            $y = $image->getHeight() - $height + $y;
        } else if ($position === 'top') {
            $x = ($image->getWidth() / 2) - ($width / 2) + $x;
        } else if ($position === 'left') {
            $y = ($image->getHeight() / 2) - ($height / 2) + $y;
        } else if ($position === 'right') {
            $x = $image->getWidth() - $width + $x;
            $y = ($image->getHeight() / 2) - ($height / 2) + $y;
        }

        // Now make a composite
        try {
            $this->imagick->compositeImage($watermark, Imagick::COMPOSITE_OVER, $x, $y);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }

        $image->hasBeenTransformed(true);
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimumInputSize(array $params, array $imageSize) {
        return InputSizeConstraint::NO_TRANSFORMATION;
    }

    /**
     * {@inheritdoc}
     */
    public function adjustParameters($ratio, array $parameters) {
        foreach (['x', 'y', 'width', 'height'] as $param) {
            if (isset($parameters[$param])) {
                $parameters[$param] = round($parameters[$param] / $ratio);
            }
        }

        return $parameters;
    }
}
