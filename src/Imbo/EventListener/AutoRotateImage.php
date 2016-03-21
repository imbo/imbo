<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface,
    Imbo\EventListener\ListenerInterface;

/**
 * Auto rotate event listener
 *
 * @author Kristoffer Brabrand <kristoffer@brabrand.no>
 * @package Event\Listeners
 */
class AutoRotateImage implements ListenerInterface {
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'images.post' => ['autoRotate' => 25],
        ];
    }

    /**
     * Autorotate images when new images are added to Imbo
     *
     * @param EventInterface $event The triggered event
     */
    public function autoRotate(EventInterface $event) {
        $event->getManager()->trigger('image.transformation.autorotate', [
            'image' => $event->getRequest()->getImage(),
        ]);
    }
}
