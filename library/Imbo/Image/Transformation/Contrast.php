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
 * Contrast transformation
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Image\Transformations
 */
class Contrast extends Transformation implements ListenerInterface {
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'image.transformation.contrast' => 'transform',
        ];
    }

    /**
     * Transform the image
     *
     * @param EventInterface $event The event instance
     */
    public function transform(EventInterface $event) {
        $params = $event->getArgument('params');

        $alpha = isset($params['sharpen']) ? (float) $params['sharpen'] : 1;
        $alpha = isset($params['alpha']) ? (float) $params['alpha'] : $alpha;
        $beta = isset($params['beta']) ? (float) $params['beta'] : 0.5;
        $sharpen = $alpha > 0;

        if ($alpha == 0) {
            return;
        }

        $beta *= $this->getQuantumRange();

        try {
            $this->imagick->sigmoidalContrastImage($sharpen, abs($alpha), $beta);

            $event->getArgument('image')->hasBeenTransformed(true);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }
}
