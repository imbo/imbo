<?php declare(strict_types=1);

namespace Imbo\EventListener;

use Imagick;
use Imbo\EventManager\EventInterface;

/**
 * Add the current Imagick instance to the active LoaderManager and OutputConverterManager.
 */
class LoaderOutputConverterImagick implements ListenerInterface, ImagickAware
{
    /**
     * @var Imagick
     */
    protected $imagick;

    public static function getSubscribedEvents(): array
    {
        return [
            'imbo.initialized' => ['populateImagickInstance'],
        ];
    }

    public function setImagick(Imagick $imagick): self
    {
        $this->imagick = $imagick;

        return $this;
    }

    /**
     * Set the Imagick instance in the loader manager and the output converter manager.
     */
    public function populateImagickInstance(EventInterface $event): void
    {
        $event->getInputLoaderManager()->setImagick($this->imagick);
        $event->getOutputConverterManager()->setImagick($this->imagick);
    }
}
