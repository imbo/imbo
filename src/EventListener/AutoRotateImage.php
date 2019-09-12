<?php
namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface;
use Imbo\EventListener\ListenerInterface;

/**
 * Auto rotate event listener
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
        $event->getTransformationManager()
            ->getTransformation('autoRotate')
            ->setImage($event->getRequest()->getImage())
            ->transform([]);
    }
}
