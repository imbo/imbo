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
class Convert extends Transformation {
    /**
     * {@inheritdoc}
     */
    public function transform(array $params) {
        if (empty($params['type'])) {
            throw new TransformationException('Missing required parameter: type', 400);
        }

        $type = $params['type'];

        if ($this->image->getExtension() === $type) {
            // The requested extension is the same as the image, no conversion is needed
            return;
        }

        try {
            $this->imagick->setImageFormat($type);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }

        $outputConverterManager = $this->event->getOutputConverterManager();

        $this->image->setMimeType($outputConverterManager->getMimetypeFromExtension($type))
                    ->setExtension($type)
                    ->hasBeenTransformed(true);
    }
}
