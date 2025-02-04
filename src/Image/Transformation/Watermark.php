<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imagick;
use ImagickException;
use Imbo\Exception\StorageException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;
use Imbo\Image\InputSizeConstraint;

/**
 * Watermark transformation
 */
class Watermark extends Transformation implements InputSizeConstraint
{
    /**
     * Default image identifier to use for watermarks
     */
    private ?string $defaultImage = null;

    /**
     * X coordinate of watermark relative to position parameters
     */
    private int $x = 0;

    /**
     * Y coordinate of watermark relative to position parameters
     */
    private int $y = 0;

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
     */
    private string $position = 'top-left';

    /**
     * Set default image identifier to use if no identifier has been specified
     */
    public function setDefaultImage(string $imageIdentifier)
    {
        $this->defaultImage = $imageIdentifier;
    }

    public function transform(array $params)
    {
        $width = !empty($params['width']) ? (int) $params['width'] : 0;
        $height = !empty($params['height']) ? (int) $params['height'] : 0;
        $imageIdentifier = !empty($params['img']) ? $params['img'] : $this->defaultImage;
        $position = !empty($params['position']) ? $params['position'] : $this->position;
        $x = !empty($params['x']) ? (int) $params['x'] : $this->x;
        $y = !empty($params['y']) ? (int) $params['y'] : $this->y;
        $opacity = (!empty($params['opacity']) ? (int) $params['opacity'] : 100) / 100;
        $image = $this->image;

        if (empty($imageIdentifier)) {
            throw new TransformationException(
                'You must specify an image identifier to use for the watermark',
                Response::HTTP_BAD_REQUEST,
            );
        }

        // Try to load watermark image from storage
        try {
            $watermarkData = $this->event->getStorage()->getImage(
                $this->event->getRequest()->getUser(),
                $imageIdentifier,
            );
        } catch (StorageException $e) {
            if ($e->getCode() == Response::HTTP_NOT_FOUND) {
                throw new TransformationException('Watermark image not found', Response::HTTP_BAD_REQUEST);
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
                $watermark->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
            }

            $watermark->evaluateImage(Imagick::EVALUATE_MULTIPLY, $opacity, Imagick::CHANNEL_ALPHA);
        }

        // Should we resize the watermark?
        if ($height || $width) {
            // Calculate width or height if not both have been specified
            if (!$height) {
                $height = ($watermarkSize['height'] / $watermarkSize['width']) * $width;
            } elseif (!$width) {
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
        } elseif ($position === 'bottom-left') {
            $y = $image->getHeight() - $height + $y;
        } elseif ($position === 'bottom-right') {
            $x = $image->getWidth() - $width + $x;
            $y = $image->getHeight() - $height + $y;
        } elseif ($position === 'center') {
            $x = ($image->getWidth() / 2) - ($width / 2) + $x;
            $y = ($image->getHeight() / 2) - ($height / 2) + $y;
        } elseif ($position === 'bottom') {
            $x = ($image->getWidth() / 2) - ($width / 2) + $x;
            $y = $image->getHeight() - $height + $y;
        } elseif ($position === 'top') {
            $x = ($image->getWidth() / 2) - ($width / 2) + $x;
        } elseif ($position === 'left') {
            $y = ($image->getHeight() / 2) - ($height / 2) + $y;
        } elseif ($position === 'right') {
            $x = $image->getWidth() - $width + $x;
            $y = ($image->getHeight() / 2) - ($height / 2) + $y;
        }

        // Now make a composite
        try {
            $this->imagick->compositeImage($watermark, Imagick::COMPOSITE_OVER, (int) $x, (int) $y);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), Response::HTTP_BAD_REQUEST, $e);
        }

        $image->setHasBeenTransformed(true);
    }

    public function getMinimumInputSize(array $params, array $imageSize): int
    {
        return InputSizeConstraint::NO_TRANSFORMATION;
    }

    public function adjustParameters(float $ratio, array $parameters): array
    {
        foreach (['x', 'y', 'width', 'height'] as $param) {
            if (isset($parameters[$param])) {
                $parameters[$param] = round($parameters[$param] / $ratio);
            }
        }

        return $parameters;
    }
}
