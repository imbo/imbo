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

use Imbo\Exception\TransformationException,
    Imbo\EventListener\ListenerInterface,
    Imbo\EventManager\EventInterface,
    ImagickException;

/**
 * Sharpen transformation
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
class Sharpen extends Transformation implements ListenerInterface {
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'image.transformation.sharpen' => 'transform',
        ];
    }

    /**
     * Transform the image
     *
     * @param EventInterface $event The event instance
     */
    public function transform(EventInterface $event) {
        $params = $event->getArgument('params');

        $preset = isset($params['preset']) ? $params['preset'] : null;

        switch ($preset) {
            case 'moderate':
                $radius = 2;
                $sigma = 1;
                $gain = 2;
                $threshold = .05;
                break;

            case 'strong':
                $radius = 2;
                $sigma = 1;
                $gain = 3;
                $threshold = .025;
                break;

            case 'extreme':
                $radius = 2;
                $sigma = 1;
                $gain = 4;
                $threshold = 0;
                break;

            case 'light':
            default:
                // Default values (with only adding ?t[]=sharpen)
                $radius = 2;
                $sigma = 1;
                $gain = 1;
                $threshold = .05;
        }

        if (isset($params['radius'])) {
            $radius = (float) $params['radius'];
        }

        if (isset($params['sigma'])) {
            $sigma = (float) $params['sigma'];
        }

        if (isset($params['gain'])) {
            $gain = (float) $params['gain'];
        }

        if (isset($params['threshold'])) {
            $threshold = (float) $params['threshold'];
        }

        try {
            $this->imagick->unsharpMaskImage($radius, $sigma, $gain, $threshold);
            $event->getArgument('image')->hasBeenTransformed(true);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }
}
