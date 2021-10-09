<?php declare(strict_types=1);
namespace Imbo\Image\Transformation;

use ImagickException;
use ImagickPixelException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;
use Imbo\Image\InputSizeConstraint;

/**
 * Rotate transformation
 */
class Rotate extends Transformation implements InputSizeConstraint
{
    /**
     * Background color of the image
     *
     * @var string
     */
    private $bg = '#000';

    /**
     * {@inheritdoc}
     */
    public function transform(array $params)
    {
        if (empty($params['angle'])) {
            throw new TransformationException('Missing required parameter: angle', Response::HTTP_BAD_REQUEST);
        }

        $angle = (int) $params['angle'];
        $bg = !empty($params['bg']) ? $this->formatColor($params['bg']) : $this->bg;

        try {
            $this->imagick->rotateImage($bg, $angle);
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
     * {@inheritdoc}
     */
    public function getMinimumInputSize(array $params, array $imageSize)
    {
        if (empty($params['angle'])) {
            throw new TransformationException('Missing required parameter: angle', Response::HTTP_BAD_REQUEST);
        }

        // If the angle of the rotation is dividable by 90, we can calculate the input
        // size for the transformation that follow. Otherwise, this will be hard, so we
        // return false to signal that we can't make any assumptions from this point on
        if ($params['angle'] % 90 === 0) {
            return ['rotation' => (int) $params['angle']];
        }

        return InputSizeConstraint::STOP_RESOLVING;
    }
}
