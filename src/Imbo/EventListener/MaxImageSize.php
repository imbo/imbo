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
     * @param array $params Parameters for the event listener
     */
    public function __construct(array $params) {
        $this->width = isset($params['width']) ? (int) $params['width'] : 0;
        $this->height = isset($params['height']) ? (int) $params['height'] : 0;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'images.post' => ['enforceMaxSize' => 25],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function enforceMaxSize(EventInterface $event) {
        $image = $event->getRequest()->getImage();

        $width = $image->getWidth();
        $height = $image->getHeight();

        if (($this->width && ($width > $this->width)) || ($this->height && ($height > $this->height))) {
            $event->getManager()->trigger('image.transformation.maxsize', [
                'image' => $image,
                'params' => [
                    'width' => $this->width,
                    'height' => $this->height,
                ],
            ]);
        }
    }
}
