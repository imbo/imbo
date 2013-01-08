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
 * Compression transformation
 *
 * @package Image
 * @subpackage Transformation
 * @author Christer Edvartsen <cogo@starzinger.net>
 */
class Compress extends Transformation implements TransformationInterface {
    /**
     * Quality of the resulting image
     *
     * @var int
     */
    private $quality;

    /**
     * Class constructor
     *
     * @param array $params Parameters for this transformation
     * @throws TransformationException
     */
    public function __construct(array $params) {
        if (empty($params['quality'])) {
            throw new TransformationException('Missing required parameter: quality', 400);
        }

        $this->quality = (int) $params['quality'];
    }

    /**
     * {@inheritdoc}
     */
    public function applyToImage(Image $image) {
        try {
            $imagick = $this->getImagick();
            $imagick->readImageBlob($image->getBlob());
            $imagick->setImageCompressionQuality($this->quality);

            $image->setBlob($imagick->getImageBlob());
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }
}
