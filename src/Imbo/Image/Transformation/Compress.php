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
 * Compression transformation
 *
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @package Image\Transformations
 */
class Compress extends Transformation implements ListenerInterface {
    /**
     * @var int
     */
    private $level;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents() {
        return [
            'image.transformation.compress' => 'transform',
            'image.transformed' => 'compress',
        ];
    }

    /**
     * Apply the compression
     *
     * @param EventInterface $event The event instance
     */
    public function compress(EventInterface $event) {
        if ($this->level === null) {
            return;
        }

        $image = $event->getArgument('image');
        $mimeType = $image->getMimeType();

        if ($mimeType === 'image/gif') {
            // No need to do anything if the image is a GIF
            return;
        }

        try {
            // Levels from 0 - 100 will work for both JPEG and PNG, although the level has different
            // meaning for these two image types. For PNG's a high level will mean more compression,
            // which usually results in a smaller file size, as for JPEG's, a high level means a
            // higher quality, resulting in a larger file size.
            $this->imagick->setImageCompressionQuality($this->level);
            $image->hasBeenTransformed(true);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), 400, $e);
        }
    }

    /**
     * Transform the image
     *
     * @param EventInterface $event The event instance
     */
    public function transform(EventInterface $event) {
        $params = $event->getArgument('params');

        if (empty($params['level'])) {
            throw new TransformationException('Missing required parameter: level', 400);
        }

        $this->level = (int) $params['level'];

        if ($this->level < 0 || $this->level > 100) {
            throw new TransformationException('level must be between 0 and 100', 400);
        }
    }
}
