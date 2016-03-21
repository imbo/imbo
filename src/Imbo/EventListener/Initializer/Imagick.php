<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\EventListener\Initializer;

use Imbo\Image\Transformation\Transformation,
    Imbo\EventListener\Imagick as ImagickListener,
    Imbo\EventListener\ListenerInterface;

/**
 * Imagick initializer
 *
 * This event listener initializer will inject the same Imagick instance into all the
 * transformation event listeners as well as the custom Imagick event listener.L
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
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
        if ($listener instanceof ImagickListener || $listener instanceof Transformation) {
            $listener->setImagick($this->imagick);
        }
    }
}
