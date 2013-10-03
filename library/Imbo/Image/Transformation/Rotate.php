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

use Imbo\Model\Image,
    Imbo\Exception\TransformationException,
    ImagickException,
    ImagickPixelException;

/**
 * Rotate transformation
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
class Rotate extends Transformation implements TransformationInterface {
    /**
     * Background color of the image
     *
     * @var string
     */
    private $bg = '#000';

    /**
     * {@inheritdoc}
     */
    public function applyToImage(Image $image, array $params = array()) {
        if (empty($params['angle'])) {
            throw new TransformationException('Missing required parameter: angle', 400);
        }

        $angle = (int) $params['angle'];
        $bg = !empty($params['bg']) ? $this->formatColor($params['bg']) : $this->bg;

        try {
            $imagick = $this->getImagick();
            $imagick->readImageBlob($image->getBlob());

            $imagick->rotateImage($bg, $angle);

            $size = $imagick->getImageGeometry();

            $image->setBlob($imagick->getImageBlob())
                  ->setWidth($size['width'])
                  ->setHeight($size['height']);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        } catch (ImagickPixelException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }
}
