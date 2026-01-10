<?php declare(strict_types=1);

namespace Imbo\Image\Transformation;

use ImagickException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;

/**
 * Strip properties and comments from an image.
 */
class Strip extends Transformation
{
    public function transform(array $params)
    {
        try {
            $this->imagick->stripImage();
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), Response::HTTP_BAD_REQUEST, $e);
        }

        // In newer versions of Imagick, it seems we need to clear and re-read
        // the data to properly clear the properties
        $data = $this->imagick->getImageBlob();
        $this->imagick->clear();
        $this->imagick->readImageBlob($data);

        $this->image->setHasBeenTransformed(true);
    }
}
