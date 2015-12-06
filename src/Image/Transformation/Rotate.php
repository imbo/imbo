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
    Imbo\Image\InputSizeConstraint,
    ImagickException,
    ImagickPixelException;

/**
 * Rotate transformation
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
class Rotate extends Transformation implements InputSizeConstraint {
    /**
     * Background color of the image
     *
     * @var string
     */
    private $bg = '#000';

    /**
     * {@inheritdoc}
     */
    public function transform(array $params) {
        if (empty($params['angle'])) {
            throw new TransformationException('Missing required parameter: angle', 400);
        }

        $image = $this->image;
        $angle = (int) $params['angle'];
        $bg = !empty($params['bg']) ? $this->formatColor($params['bg']) : $this->bg;

        try {
            $this->imagick->rotateImage($bg, $angle);

            $size = $this->imagick->getImageGeometry();

            $image->setWidth($size['width'])
                  ->setHeight($size['height'])
                  ->hasBeenTransformed(true);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        } catch (ImagickPixelException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimumInputSize(array $params, array $imageSize) {
        if (empty($params['angle'])) {
            throw new TransformationException('Missing required parameter: angle', 400);
        }

        // If the angle of the rotation is dividable by 90, we can calculate the input
        // size for the transformation that follow. Otherwise, this will be hard, so we
        // return false to signal that we can't make any assumptions from this point on
        if ($params['angle'] % 90 === 0) {
            return ['rotation' => (int) $params['angle']];
        }

        return false;
    }
}
