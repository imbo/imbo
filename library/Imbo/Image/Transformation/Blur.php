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
 * Blur transformation
 *
 * @author Kristoffer Brabrand <kristoffer@brabrand.no>
 * @package Image\Transformations
 */
class Blur extends Transformation implements ListenerInterface {
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'image.transformation.blur' => 'transform',
        ];
    }

    /**
     * Transform the image
     *
     * @param EventInterface $event The event instance
     */
    public function transform(EventInterface $event) {
        $params = $event->getArgument('params');

        $type = isset($params['type']) ? $params['type'] : 'regular';

        if (!in_array($type, ['regular', 'adaptive', 'motion', 'radial'])) {
            throw new TransformationException('Unknown blur type: ' . $type, 400);
        }

        switch ($type) {
            default:
                return $this->blur($event);
        }
    }

    /**
     * Perform regular blur on the image
     *
     * @param EventInterface $event The event instance
     */
    private function blur(EventInterface $event) {
        $params = $event->getArgument('params');

        foreach (['radius', 'sigma'] as $param) {
            if (!isset($params[$param])) {
                throw new TransformationException('Missing required parameter: ' . $param, 400);
            }
        }

        if (isset($params['radius'])) {
            $radius = (float) $params['radius'];
        }

        if (isset($params['sigma'])) {
            $sigma = (float) $params['sigma'];
        }

        try {
            $this->imagick->blurImage($radius, $sigma);
            $event->getArgument('image')->hasBeenTransformed(true);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }
}
