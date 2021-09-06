<?php declare(strict_types=1);
namespace Imbo\Image\InputLoader;

use Imagick;

class Basic implements InputLoaderInterface
{
    public function getSupportedMimeTypes(): array
    {
        return [
            'image/png'  => 'png',
            'image/jpeg' => 'jpg',
            'image/gif'  => 'gif',
            'image/tiff' => 'tif',
        ];
    }

    public function load(Imagick $imagick, string $blob, string $mimeType)
    {
        $imagick->readImageBlob($blob);
    }
}
