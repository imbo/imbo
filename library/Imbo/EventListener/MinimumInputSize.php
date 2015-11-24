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

use Imbo\EventManager\EventInterface;

/**
 * Minimum input size listener
 *
 * This event listener will go through the transformation chain and trigger an event that
 * image transformations can listen for in order to report the minimum input size they can
 * accept. For instance, if a `resize`-transformation has been requested with a width of
 * 1024 pixels, we don't necessarily need to use a source image that is 10000 pixels wide,
 * but could make do with one that is 2048 pixels wide. This allows us to perform certain
 * optimizations during image loading and transforming.
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Event\Listeners
 */
class MinimumInputSize implements ListenerInterface {
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'image.get'  => ['findMinimumInputSize' => 20],
            'image.head' => ['findMinimumInputSize' => 20],
        ];
    }

    public function findMinimumInputSize(EventInterface $event) {
        $request = $event->getRequest();
        $transformations = $request->getTransformations();
        $eventManager = $event->getManager();
        $image = $event->getResponse()->getModel();

        // Fall back if no transformations are applied to image
        if (!$transformations) {
            return;
        }

        // Allow each transformation to report their minimum input size
        foreach ($transformations as $transformation) {
            $transformationName = strtolower($transformation['name']);
            $eventName = 'image.transformation.' . $transformationName . '.report-min-input-size';

            $eventManager->trigger(
                $transformationName, [
                    'image' => $image,
                    'params' => $transformation['params'],
                ]
            );
        }
        exit;
    }
}
