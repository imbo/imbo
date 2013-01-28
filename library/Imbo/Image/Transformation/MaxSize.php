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
 * MaxSize transformation
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
class MaxSize extends Transformation implements TransformationInterface {
    /**
     * Max width of the image
     *
     * @var int
     */
    private $maxWidth;

    /**
     * Max height of the image
     *
     * @var int
     */
    private $maxHeight;

    /**
     * Class constructor
     *
     * @param array $params Parameters for this transformation
     */
    public function __construct(array $params) {
        $this->maxWidth = !empty($params['width']) ? (int) $params['width'] : 0;
        $this->maxHeight = !empty($params['height']) ? (int) $params['height'] : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToImage(Image $image) {
        try {
            $sourceWidth  = $image->getWidth();
            $sourceHeight = $image->getHeight();

            $width  = $this->maxWidth  ?: $sourceWidth;
            $height = $this->maxHeight ?: $sourceHeight;

            // Figure out original ratio
            $ratio = $sourceWidth / $sourceHeight;

            // Is the original image larger than the max-parameters?
            if (($sourceWidth > $width) || ($sourceHeight > $height)) {
                if (($width / $height) > $ratio) {
                    $width  = round($height * $ratio);
                } else {
                    $height = round($width / $ratio);
                }
            } else {
                // Original image is smaller than the max-parameters, don't transform
                return;
            }

            $imagick = $this->getImagick();
            $imagick->setOption('jpeg:size', $width . 'x' . $height);
            $imagick->readImageBlob($image->getBlob());
            $imagick->thumbnailImage($width, $height);

            $size = $imagick->getImageGeometry();

            $image->setBlob($imagick->getImageBlob())
                  ->setWidth($size['width'])
                  ->setHeight($size['height']);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }
}
