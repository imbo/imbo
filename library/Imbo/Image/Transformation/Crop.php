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
    ImagickException;

/**
 * Crop transformation
 *
 * @package Image
 * @subpackage Transformation
 * @author Christer Edvartsen <cogo@starzinger.net>
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
     * Width of the crop
     *
     * @var int
     */
    private $width;

    /**
     * Height of the crop
     *
     * @var int
     */
    private $height;

    /**
     * Class constructor
     *
     * @param array $params Parameters for this transformation
     * @throws TransformationException
     */
    public function __construct(array $params) {
        foreach (array('width', 'height') as $param) {
            if (!isset($params[$param])) {
                throw new TransformationException('Missing required parameter: ' . $param, 400);
            }
        }

        $this->width = (int) $params['width'];
        $this->height = (int) $params['height'];

        if (!empty($params['x'])) {
            $this->x = (int) $params['x'];
        }

        if (!empty($params['y'])) {
            $this->y = (int) $params['y'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function applyToImage(Image $image) {
        try {
            $imagick = $this->getImagick();
            $imagick->readImageBlob($image->getBlob());
            $imagick->cropImage($this->width, $this->height, $this->x, $this->y);

            $size = $imagick->getImageGeometry();

            $image->setBlob($imagick->getImageBlob())
                  ->setWidth($size['width'])
                  ->setHeight($size['height']);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }
}
