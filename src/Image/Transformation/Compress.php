<?php
namespace Imbo\Image\Transformation;

use Imbo\Exception\TransformationException;
use Imbo\EventListener\ListenerInterface;
use Imbo\EventManager\EventInterface;
use ImagickException;

/**
 * Compression transformation
 *
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
