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

use Imbo\Image\Image,
    Imbo\Exception\TransformationException,
    ImagickException,
    ImagickPixelException;

/**
 * Rotate transformation
 *
 * @package Image
 * @subpackage Transformation
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class Rotate extends Transformation implements TransformationInterface {
    /**
     * Angle of the rotation
     *
     * @var int
     */
    private $angle;

    /**
     * Background color of the image
     *
     * @var string
     */
    private $bg = '#000';

    /**
     * Class constructor
     *
     * @param array $params Parameters for this transformation
     * @throws TransformationException
     */
    public function __construct(array $params) {
        if (empty($params['angle'])) {
            throw new TransformationException('Missing required parameter: angle', 400);
        }

        $this->angle = (int) $params['angle'];

        if (!empty($params['bg'])) {
            $this->bg = $this->formatColor($params['bg']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function applyToImage(Image $image) {
        try {
            $imagick = $this->getImagick();
            $imagick->readImageBlob($image->getBlob());

            $imagick->rotateImage($this->bg, $this->angle);

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
