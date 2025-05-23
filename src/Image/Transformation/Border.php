<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use Imagick;
use ImagickDraw;
use ImagickException;
use ImagickPixelException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;
use Imbo\Image\InputSizeConstraint;

/**
 * Border transformation
 */
class Border extends Transformation implements InputSizeConstraint
{
    /**
     * Color of the border
     */
    private string $color = '#000';

    /**
     * Width of the border
     */
    private int $width = 1;

    /**
     * Height of the border
     */
    private int $height = 1;

    /**
     * Border mode, "inline" or "outbound"
     */
    private string $mode = 'outbound';

    public function transform(array $params): void
    {
        $color = !empty($params['color']) ? $this->formatColor($params['color']) : $this->color;
        $width = isset($params['width']) ? (int) $params['width'] : $this->width;
        $height = isset($params['height']) ? (int) $params['height'] : $this->height;
        $mode = !empty($params['mode']) ? $params['mode'] : $this->mode;

        try {
            if ($mode === 'outbound') {
                // Paint the border outside of the image, increasing the width/height
                if ($this->imagick->getImageAlphaChannel()) {
                    // If we have an alpha channel and call `borderImage()`, Imagick will remove
                    // the alpha channel - if we have an alpha channel, use an alternative approach
                    $this->expandImage($color, $width, $height);
                } else {
                    // If we don't have an alpha channel, use the more cost-efficient `borderImage()`
                    $this->imagick->borderImage($color, $width, $height);
                }
            } else {
                // Paint the border inside of the image, keeping the orignal width/height
                $this->drawBorderInside($color, $width, $height);
            }
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), Response::HTTP_BAD_REQUEST, $e);
        } catch (ImagickPixelException $e) {
            throw new TransformationException($e->getMessage(), Response::HTTP_BAD_REQUEST, $e);
        }

        $size = $this->imagick->getImageGeometry();

        $this->image->setWidth($size['width'])
                    ->setHeight($size['height'])
                    ->setHasBeenTransformed(true);
    }

    /**
     * Expand the image so that we can fit the width and height of the borders specified on each
     * side, than copy the original image to the center of the canvas.
     *
     * @param string $color
     * @param integer $borderWidth
     * @param integer $borderHeight
     */
    private function expandImage($color, $borderWidth, $borderHeight)
    {
        $imageWidth = $this->image->getWidth();
        $imageHeight = $this->image->getHeight();

        $original = clone $this->imagick;

        // Clear the original and make the canvas
        $this->imagick->clear();

        $this->imagick->newImage(
            $imageWidth  + ($borderWidth  * 2),
            $imageHeight + ($borderHeight * 2),
            $color,
        );
        $this->imagick->setImageAlphaChannel(Imagick::ALPHACHANNEL_OPAQUE);
        $this->imagick->setImageFormat($this->image->getExtension());

        $this->imagick->compositeImage(
            $original,
            Imagick::COMPOSITE_COPY,
            $borderWidth,
            $borderHeight,
        );
    }

    /**
     * Draw border inside (on top of) the existing image
     *
     * @param string $color
     * @param integer $borderWidth
     * @param integer $borderHeight
     */
    private function drawBorderInside($color, $borderWidth, $borderHeight)
    {
        $imageWidth = $this->image->getWidth();
        $imageHeight = $this->image->getHeight();

        $rect = new ImagickDraw();
        $rect->setStrokeColor($color);
        $rect->setFillColor($color);
        $rect->setStrokeAntialias(false);

        // Left
        $rect->rectangle(0, 0, $borderWidth - 1, $imageHeight);

        // Right
        $rect->rectangle($imageWidth - $borderWidth, 0, $imageWidth, $imageHeight);

        // Top
        $rect->rectangle(0, 0, $imageWidth, $borderHeight - 1);

        // Bottom
        $rect->rectangle(0, $imageHeight - $borderHeight, $imageWidth, $imageHeight);

        // Draw the border
        $this->imagick->drawImage($rect);
    }

    public function getMinimumInputSize(array $params, array $imageSize): int
    {
        return InputSizeConstraint::NO_TRANSFORMATION;
    }

    public function adjustParameters(float $ratio, array $parameters): array
    {
        foreach (['width', 'height'] as $param) {
            if (isset($parameters[$param])) {
                $parameters[$param] = round($parameters[$param] / $ratio);
            }
        }

        return $parameters;
    }
}
