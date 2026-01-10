<?php declare(strict_types=1);

namespace Imbo\EventListener\Initializer;

use Imagick as BaseImagick;
use Imbo\EventListener\ImagickAware;
use Imbo\EventListener\ListenerInterface;

/**
 * Imagick initializer.
 *
 * This event listener initializer will inject the same Imagick instance into all the
 * transformation event listeners as well as the custom Imagick event listener.
 */
class Imagick implements InitializerInterface
{
    /**
     * Imagick instance used by Imbo's built in image transformations.
     */
    private BaseImagick $imagick;

    public function __construct(?BaseImagick $imagick = null)
    {
        if (null === $imagick) {
            $imagick = new BaseImagick();
            $imagick->setOption('png:exclude-chunks', 'all');
        }

        $this->imagick = $imagick;
    }

    /**
     * Injects the Imagick instance into some event listeners.
     */
    public function initialize(ListenerInterface $listener): void
    {
        if ($listener instanceof ImagickAware) {
            $listener->setImagick($this->imagick);
        }
    }
}
