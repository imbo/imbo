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
class Compress extends Transformation {
    /**
     * @var int
     */
    private $level;

    /**
     * {@inheritdoc}
     */
    public function transform(array $params) {
        if (empty($params['level'])) {
            throw new TransformationException('Missing required parameter: level', 400);
        }

        $this->level = (int) $params['level'];

        if ($this->level < 0 || $this->level > 100) {
            throw new TransformationException('level must be between 0 and 100', 400);
        }

        $this->image->setOutputQualityCompression($this->level);
        return $this;
    }
}
