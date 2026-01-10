<?php declare(strict_types=1);

namespace Imbo\EventListener;

use Imbo\EventManager\EventInterface;

/**
 * Auto rotate event listener.
 */
class AutoRotateImage implements ListenerInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'images.post' => ['autoRotate' => 25],
        ];
    }

    /**
     * Autorotate images when new images are added to Imbo.
     *
     * @param EventInterface $event The triggered event
     */
    public function autoRotate(EventInterface $event)
    {
        $event->getTransformationManager()
            ->getTransformation('autoRotate')
            ->setImage($event->getRequest()->getImage())
            ->transform([]);
    }
}
