<?php declare(strict_types=1);

namespace Imbo\Image\Transformation;

use ImagickException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;
use Imbo\Image\InputSizeConstraint;

/**
 * Thumbnail transformation.
 */
class Thumbnail extends Transformation implements InputSizeConstraint
{
    /**
     * Width of the thumbnail.
     *
     * @var int
     */
    private $width = 50;

    /**
     * Height of the thumbnail.
     *
     * @var int
     */
    private $height = 50;

    /**
     * Fit type.
     *
     * The thumbnail fit style. 'inset' or 'outbound'
     *
     * @var string
     */
    private $fit = 'outbound';

    public function transform(array $params)
    {
        $width = !empty($params['width']) ? (int) $params['width'] : $this->width;
        $height = !empty($params['height']) ? (int) $params['height'] : $this->height;
        $fit = !empty($params['fit']) ? $params['fit'] : $this->fit;

        try {
            if ('inset' === $fit) {
                $this->imagick->thumbnailImage($width, $height, true);
            } else {
                $this->imagick->cropThumbnailImage($width, $height);
            }
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), Response::HTTP_BAD_REQUEST, $e);
        }

        $size = $this->imagick->getImageGeometry();

        $this->image->setWidth($size['width'])
                    ->setHeight($size['height'])
                    ->setHasBeenTransformed(true);
    }

    public function getMinimumInputSize(array $params, array $imageSize)
    {
        $fit = isset($params['fit']) ? $params['fit'] : $this->fit;
        $width = !empty($params['width']) ? (int) $params['width'] : $this->width;
        $height = !empty($params['height']) ? (int) $params['height'] : $this->height;
        $ratio = $this->image->getWidth() / $this->image->getHeight();

        if ('inset' !== $fit) {
            return ['width' => $width, 'height' => $height];
        }

        $sourceWidth = $imageSize['width'];
        $sourceHeight = $imageSize['height'];

        $ratioX = $width / $sourceWidth;
        $ratioY = $height / $sourceHeight;

        if ($ratioX === $ratioY) {
            return ['width' => $width, 'height' => $height];
        } elseif ($ratioX < $ratioY) {
            return ['width' => $width, 'height' => (int) max(1, $ratioX * $sourceHeight)];
        }

        return ['width' => (int) max(1, $ratioY * $sourceWidth), 'height' => $height];
    }
}
