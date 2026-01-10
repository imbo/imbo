<?php declare(strict_types=1);

namespace Imbo\Image\Transformation;

use ImagickException;
use Imbo\Exception\TransformationException;
use Imbo\Http\Response\Response;

/**
 * Convert transformation.
 *
 * This transformation can be used to convert the image from one type to another.
 */
class Convert extends Transformation
{
    public function transform(array $params)
    {
        if (empty($params['type'])) {
            throw new TransformationException('Missing required parameter: type', Response::HTTP_BAD_REQUEST);
        }

        $type = $params['type'];

        if ($this->image->getExtension() === $type) {
            // The requested extension is the same as the image, no conversion is needed
            return;
        }

        try {
            $this->imagick->setImageFormat($type);
        } catch (ImagickException $e) {
            throw new TransformationException($e->getMessage(), Response::HTTP_BAD_REQUEST, $e);
        }

        $outputConverterManager = $this->event->getOutputConverterManager();

        $this->image->setMimeType($outputConverterManager->getMimeTypeFromExtension($type))
                    ->setExtension($type)
                    ->setHasBeenTransformed(true);
    }
}
