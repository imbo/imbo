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
 * Convert transformation
 *
 * This transformation can be used to convert the image from one type to another.
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
class Convert extends Transformation implements TransformationInterface {
    /**
     * Type we want to convert to
     *
     * @var string
     */
    private $type;

    /**
     * Class constructor
     *
     * @param array $params Parameters for this transformation
     * @throws TransformationException
     */
    public function __construct(array $params) {
        if (empty($params['type'])) {
            throw new TransformationException('Missing required parameter: type', 400);
        }

        $this->type = $params['type'];
    }

    /**
     * {@inheritdoc}
     */
    public function applyToImage(Image $image) {
        if ($image->getExtension() === $this->type) {
            // The requested extension is the same as the image, no conversion is needed
            return;
        }

        try {
            $imagick = $this->getImagick();
            $imagick->readImageBlob($image->getBlob());

            $imagick->setImageFormat($this->type);
            $mimeType = array_search($this->type, Image::$mimeTypes);

            $image->setBlob($imagick->getImageBlob());
            $image->setMimeType($mimeType);
            $image->setExtension($this->type);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }
}
