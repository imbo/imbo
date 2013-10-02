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
    Imbo\EventListener\ListenerInterface,
    Imbo\Image\Transformation\MaxSize;

/**
 * Max image size event listener
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @package Event\Listeners
 */
class MaxImageSize implements ListenerInterface {
    /**
     * Max width
     *
     * @var int
     */
    private $width;

    /**
     * Max height
     *
     * @var int
     */
    private $height;

    /**
     * Class constructor
     *
     * @param int $width Max width
     * @param int $height Max height
     */
    public function __construct($width = null, $height = null) {
        $this->width = (int) $width;
        $this->height = (int) $height;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return array(
            'image.put' => array('enforceMaxSize' => 25),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function enforceMaxSize(EventInterface $event) {
        $image = $event->getRequest()->getImage();

        $width = $image->getWidth();
        $height = $image->getHeight();

        if (($this->width && ($width > $this->width)) || ($this->height && ($height > $this->height))) {
            $transformation = new MaxSize(array(
                'width' => $this->width,
                'height' => $this->height,
            ));
            $transformation->applyToImage($image);
        }
    }
}
