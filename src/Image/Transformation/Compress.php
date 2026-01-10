<?php declare(strict_types=1);

namespace Imbo\Image\Transformation;

use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;

/**
 * Compression transformation.
 */
class Compress extends Transformation
{
    private int $level;

    public function transform(array $params)
    {
        if (empty($params['level'])) {
            throw new TransformationException('Missing required parameter: level', Response::HTTP_BAD_REQUEST);
        }

        $this->level = (int) $params['level'];

        if ($this->level < 0 || $this->level > 100) {
            throw new TransformationException('level must be between 0 and 100', Response::HTTP_BAD_REQUEST);
        }

        $this->image->setOutputQualityCompression($this->level);
    }
}
