<?php
namespace Imbo\Image\Transformation;

use Imbo\Exception\TransformationException;
use Imbo\Image\InputSizeConstraint;
use ImagickException;

/**
 * Thumbnail transformation
 */
class Thumbnail extends Transformation implements InputSizeConstraint {
    /**
     * Width of the thumbnail
     *
     * @var int
     */
    private $width = 50;

    /**
     * Height of the thumbnail
     *
     * @var int
     */
    private $height = 50;

    /**
     * Fit type
     *
     * The thumbnail fit style. 'inset' or 'outbound'
     *
     * @var string
     */
    private $fit = 'outbound';

    /**
     * {@inheritdoc}
     */
    public function transform(array $params) {
        $width = !empty($params['width']) ? (int) $params['width'] : $this->width;
        $height = !empty($params['height']) ? (int) $params['height'] : $this->height;
        $fit = !empty($params['fit']) ? $params['fit'] : $this->fit;

        try {
            if ($fit === 'inset') {
                $this->imagick->thumbnailImage($width, $height, true);
            } else {
                $this->imagick->cropThumbnailImage($width, $height);
            }
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }

        $size = $this->imagick->getImageGeometry();

        $this->image->setWidth($size['width'])
                    ->setHeight($size['height'])
                    ->hasBeenTransformed(true);
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimumInputSize(array $params, array $imageSize) {
        $fit = isset($params['fit']) ? $params['fit'] : $this->fit;
        $width = !empty($params['width']) ? (int) $params['width'] : $this->width;
        $height = !empty($params['height']) ? (int) $params['height'] : $this->height;
        $ratio = $this->image->getWidth() / $this->image->getHeight();

        if ($fit !== 'inset') {
            return ['width' => $width, 'height' => $height];
        }

        $sourceWidth = $imageSize['width'];
        $sourceHeight = $imageSize['height'];

        $ratioX = $width  / $sourceWidth;
        $ratioY = $height / $sourceHeight;

        if ($ratioX === $ratioY) {
            return ['width' => $width, 'height' => $height];
        } else if ($ratioX < $ratioY) {
            return ['width' => $width, 'height' => (int) max(1, $ratioX * $sourceHeight)];
        }

        return ['width' => (int) max(1, $ratioY * $sourceWidth), 'height' => $height];
    }
}
