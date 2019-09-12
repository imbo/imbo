<?php
namespace Imbo\EventListener\Initializer;

use Imbo\Image\Transformation\Transformation;
use Imbo\EventListener\ImagickAware;
use Imbo\EventListener\ListenerInterface;

/**
 * Imagick initializer
 *
 * This event listener initializer will inject the same Imagick instance into all the
 * transformation event listeners as well as the custom Imagick event listener.
 *
 * @package Event\Listeners
 */
class Imagick implements InitializerInterface {
    /**
     * Imagick instance used by Imbo's built in image transformations
     *
     * @var \Imagick
     */
    private $imagick;

    /**
     * Class constructor
     *
     * @param \Imagick $imagick An optional Imagick instance
     */
    public function __construct(\Imagick $imagick = null) {
        if ($imagick === null) {
            $imagick = new \Imagick();
            $imagick->setOption('png:exclude-chunks', 'all');
        }

        $this->imagick = $imagick;
    }

    /**
     * Injects the Imagick instance into some event listeners
     *
     * @param ListenerInterface $listener An event listener
     */
    public function initialize(ListenerInterface $listener) {
        if ($listener instanceof ImagickAware) {
            $listener->setImagick($this->imagick);
        }
    }
}
