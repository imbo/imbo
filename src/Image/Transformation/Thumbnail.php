<?php
/**
 * This file is part of the Imbo package
 *
 * (c) Christer Edvartsen <cogo@starzinger.net>
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 */

namespace Imbo\Image\Transformation;

use Imbo\Exception\TransformationException,
    Imbo\EventListener\ListenerInterface,
    Imbo\EventManager\EventInterface,
    ImagickException;

/**
 * Thumbnail transformation
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
class Thumbnail extends Transformation implements ListenerInterface {
    /**
     * Width of the thumbnail
     *
     * @var int
     */
    private $width = 50;

    /**
     * Height of the thumbnail
     *
     * @var int
     */
    private $height = 50;

    /**
     * Fit type
     *
     * The thumbnail fit style. 'inset' or 'outbound'
     *
     * @var string
     */
    private $fit = 'outbound';

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'image.transformation.thumbnail' => 'transform',
        ];
    }

    /**
     * Transform the image
     *
     * @param EventInterface $event The event instance
     */
    public function transform(EventInterface $event) {
        $image = $event->getArgument('image');
        $params = $event->getArgument('params');

        $width = !empty($params['width']) ? (int) $params['width'] : $this->width;
        $height = !empty($params['height']) ? (int) $params['height'] : $this->height;
        $fit = !empty($params['fit']) ? $params['fit'] : $this->fit;

        try {
            $this->imagick->setOption('jpeg:size', $width . 'x' . $height);

            if ($fit === 'inset') {
                $this->imagick->thumbnailimage($width, $height, true);
            } else {
                $this->imagick->cropThumbnailImage($width, $height);
            }

            $size = $this->imagick->getImageGeometry();

            $image->setWidth($size['width'])
                  ->setHeight($size['height'])
                  ->hasBeenTransformed(true);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }
}
