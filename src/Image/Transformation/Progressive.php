<?php
namespace Imbo\Image\Transformation;

use Imbo\Exception\TransformationException,
    Imbo\EventListener\ListenerInterface,
    Imbo\EventManager\EventInterface,
    Imagick,
    ImagickException;

/**
 * Progressive image transformation
 *
 * @package Image\Transformations
 */
class Progressive extends Transformation {
    /**
     * {@inheritdoc}
     */
    public function transform(array $params) {
        try {
            $this->imagick->setInterlaceScheme(Imagick::INTERLACE_PLANE);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }

        $this->image->hasBeenTransformed(true);
    }
}
