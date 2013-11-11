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
    Imbo\EventListener\ListenerInterface,
    Imbo\EventManager\EventInterface,
    ImagickException;

/**
 * Convert transformation
 *
 * This transformation can be used to convert the image from one type to another.
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
class Convert extends Transformation implements ListenerInterface {
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return array(
            'image.transformation.convert' => 'transform',
        );
    }

    /**
     */
    public function transform(EventInterface $event) {
        $image = $event->getArgument('image');
        $params = $event->getArgument('params');

        if (empty($params['type'])) {
            throw new TransformationException('Missing required parameter: type', 400);
        }

        $type = $params['type'];

        if ($image->getExtension() === $type) {
            // The requested extension is the same as the image, no conversion is needed
            return;
        }

        try {
            $imagick = $this->getImagick();
            $imagick->readImageBlob($image->getBlob());

            $imagick->setImageFormat($type);
            $mimeType = array_search($type, Image::$mimeTypes);

            $image->setBlob($imagick->getImageBlob());
            $image->setMimeType($mimeType);
            $image->setExtension($type);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }
}
