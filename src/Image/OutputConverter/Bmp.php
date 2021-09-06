<?php declare(strict_types=1);
namespace Imbo\Image\OutputConverter;

use Imagick;
use ImagickException;
use Imbo\Exception\OutputConverterException;
use Imbo\Model\Image;

class Bmp implements OutputConverterInterface
{
    public function getSupportedMimeTypes(): array
    {
        return [
            'image/bmp' => 'bmp',
        ];
    }

    public function convert(Imagick $imagick, Image $image, string $extension, string $mime)
    {
        try {
            $imagick->setImageFormat($extension);
        } catch (ImagickException $e) {
            throw new OutputConverterException($e->getMessage(), 400, $e);
        }

        $image->setHasBeenTransformed(true);
    }
}
