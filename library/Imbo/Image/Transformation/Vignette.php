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

use Imbo\Image\Transformation\Transformation,
    Imbo\Exception\TransformationException,
    Imbo\EventListener\ListenerInterface,
    Imbo\EventManager\EventInterface,
    Imagick,
    ImagickException;

/**
 * Vignette transformation
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Image\Transformations
 */
class Vignette extends Transformation implements ListenerInterface {
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return array(
            'image.transformation.vignette' => 'transform',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function transform(EventInterface $event) {
        $params = $event->getArgument('params');
        $color1 = $this->formatColor(isset($params['color1']) ? $params['color1'] : 'none');
        $color2 = $this->formatColor(isset($params['color2']) ? $params['color2'] : 'black');
        $crop   = isset($params['crop']) ? $params['crop'] : 1.5;

        $image  = $event->getArgument('image');
        $width  = $image->getWidth();
        $height = $image->getHeight();

        $cropX = floor($width  * $crop);
        $cropY = floor($height * $crop);

        $vignette = new Imagick();
        $vignette->newPseudoImage($cropX, $cropY, 'radial-gradient:' . $color1 . '-' . $color2);
        $vignette->cropImage(
            $width,
            $height,
            floor(($cropX - $width)  / 2),
            floor(($cropY - $height) / 2)
        );

        try {
            $this->imagick->compositeImage($vignette, Imagick::COMPOSITE_MULTIPLY, 0, 0);

            $image->hasBeenTransformed(true);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }
}
