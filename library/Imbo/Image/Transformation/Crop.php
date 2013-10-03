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
    ImagickException;

/**
 * Crop transformation
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
class Crop extends Transformation implements TransformationInterface {
    /**
     * X coordinate of the top left corner of the crop
     *
     * @var int
     */
    private $x = 0;

    /**
     * Y coordinate of the top left corner of the crop
     *
     * @var int
     */
    private $y = 0;

    /**
     * {@inheritdoc}
     */
    public function applyToImage(Image $image, array $params = array()) {
        foreach (array('width', 'height') as $param) {
            if (!isset($params[$param])) {
                throw new TransformationException('Missing required parameter: ' . $param, 400);
            }
        }

        $width = (int) $params['width'];
        $height = (int) $params['height'];

        $x = !empty($params['x']) ? (int) $params['x'] : $this->x;
        $y = !empty($params['y']) ? (int) $params['y'] : $this->y;

        try {
            if ($this->x === 0 && $this->y === 0 &&
                $image->getWidth() <= $this->width &&
                $image->getHeight() <= $this->height) {
                    return;
            }

            $imagick = $this->getImagick();
            $imagick->readImageBlob($image->getBlob());
            $imagick->cropImage($width, $height, $x, $y);

            $size = $imagick->getImageGeometry();

            $image->setBlob($imagick->getImageBlob())
                  ->setWidth($size['width'])
                  ->setHeight($size['height']);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }
}
