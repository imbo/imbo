<?php
namespace Imbo\Image\Transformation;

use Imbo\Exception\TransformationException;
use Imbo\EventListener\ListenerInterface;
use Imbo\EventManager\EventInterface;
use Imagick;
use ImagickException;

/**
 * Progressive image transformation
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
