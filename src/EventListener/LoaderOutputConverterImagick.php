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
 * Add the current Imagick instance to the active LoaderManager and OutputConverterManager
 *
 * @author Mats Lindh <mats@lindh.no>
 * @package Event\Listeners
 */
class LoaderOutputConverterImagick implements ListenerInterface, ImagickAware {
    /**
     * @var \Imagick
     */
    protected $imagick;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'imbo.initialized' => ['populateImagickInstance'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function setImagick(\Imagick $imagick) {
        $this->imagick = $imagick;

        return $this;
    }

    /**
     * Set the Imagick instance in the loader manager and the output converter manager
     *
     * @param EventInterface $event
     */
    public function populateImagickInstance(EventInterface $event) {
        $event->getLoaderManager()->setImagick($this->imagick);
        $event->getOutputConverterManager()->setImagick($this->imagick);
    }
}
