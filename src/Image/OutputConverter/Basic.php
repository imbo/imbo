<?php declare(strict_types=1);

namespace Imbo\Image\OutputConverter;

use Imagick;
use ImagickException;
use Imbo\Exception\OutputConverterException;
use Imbo\Http\Response\Response;
use Imbo\Model\Image;

class Basic implements OutputConverterInterface
{
    public function getSupportedMimeTypes(): array
    {
        return [
            'image/jpeg' => [
                'jpg',
                'jpeg',
            ],
            'image/png' => 'png',
            'image/gif' => 'gif',
        ];
    }

    public function convert(Imagick $imagick, Image $image, string $extension, ?string $mimeType = null)
    {
        try {
            $imagick->setImageFormat($extension);

            // Levels from 0 - 100 will work for both JPEG and PNG, although the level has different
            // meaning for these two image types. For PNG's a high level will mean more compression,
            // which usually results in a smaller file size, as for JPEG's, a high level means a
            // higher quality, resulting in a larger file size.
            if (null !== $image->getOutputQualityCompression() && ('image/gif' !== $mimeType)) {
                $imagick->setImageCompressionQuality($image->getOutputQualityCompression());
            }
        } catch (ImagickException $e) {
            throw new OutputConverterException($e->getMessage(), Response::HTTP_BAD_REQUEST, $e);
        }

        $image->setHasBeenTransformed(true);
    }
}
