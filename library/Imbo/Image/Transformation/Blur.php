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
            case 'motion':
                return $this->motionBlur($event);
            case 'adaptive':
                return $this->blur($event, true);
            default:
                return $this->blur($event, false);
        }
    }

    /**
     * Check that all params are present
     *
     * @param array $params Transformation parameter list
     * @param array $required Array with required parameters
     * @throws TransformationException
     */
    private function checkRequiredParams(array $params, array $required) {
        foreach ($required as $param) {
            if (!isset($params[$param])) {
                throw new TransformationException('Missing required parameter: ' . $param, 400);
            }
        }
    }

    /**
     * Add adaptive or regular blur to the image
     *
     * @param EventInterface $event The event instance
     * @param bool $adaptive Whether or not to perform adaptive blur
     */
    private function blur(EventInterface $event, $adaptive = false) {
        $params = $event->getArgument('params');

        $this->checkRequiredParams($params, ['radius', 'sigma']);

        if (isset($params['radius'])) {
            $radius = (float) $params['radius'];
        }

        if (isset($params['sigma'])) {
            $sigma = (float) $params['sigma'];
        }

        try {
            if ($adaptive) {
                $this->imagick->adaptiveBlurImage($radius, $sigma);
            } else {
                $this->imagick->blurImage($radius, $sigma);
            }

            $event->getArgument('image')->hasBeenTransformed(true);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }

    /**
     * Add motion blur to the image
     *
     * @param EventInterface $event The event instance
     */
    private function motionBlur(EventInterface $event) {
        $params = $event->getArgument('params');

        $this->checkRequiredParams($params, ['radius', 'sigma', 'angle']);

        if (isset($params['radius'])) {
            $radius = (float) $params['radius'];
        }

        if (isset($params['sigma'])) {
            $sigma = (float) $params['sigma'];
        }

        if (isset($params['angle'])) {
            $angle = (float) $params['angle'];
        }

        try {
            $this->imagick->motionBlurImage(0, $sigma, $angle);
            $event->getArgument('image')->hasBeenTransformed(true);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }
}
