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
    ImagickException,
    ImagickPixelException;

/**
 * Rotate transformation
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
class Rotate extends Transformation {
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
}
