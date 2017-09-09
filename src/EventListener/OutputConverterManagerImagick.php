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
 * Add the current Imagick instance to the active OutputConverterManager
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Event\Listeners
 */
class OutputConverterManagerImagick implements ListenerInterface, ImagickAware {
    protected $imagick;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'image.loaded' => ['populateImagickInstance'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function setImagick(\Imagick $imagick) {
        $this->imagick = $imagick;
    }

    public function populateImagickInstance(EventInterface $event) {
        $event->getOutputConverterManager()->setImagick($this->imagick);
    }
}