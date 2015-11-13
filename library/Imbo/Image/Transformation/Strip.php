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
 * Strip properties and comments from an image
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
class Strip extends Transformation implements ListenerInterface {
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'image.transformation.strip' => 'transform',
        ];
    }

    /**
     * Transform the image
     *
     * @param EventInterface $event The event instance
     */
    public function transform(EventInterface $event) {
        try {
            $this->imagick->stripImage();

            // In newer versions of Imagick, it seems we need to clear and re-read
            // the data to properly clear the properties
            $version = $this->imagick->getVersion();
            $version = preg_replace('#.*?(\d+\.\d+\.\d+).*#', '$1', $version['versionString']);

            if (version_compare($version, '6.8.0') >= 0){
                $data = $this->imagick->getImagesBlob();
                $this->imagick->clear();
                $this->imagick->readImageBlob($data);
            }

            $event->getArgument('image')->hasBeenTransformed(true);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }
}
