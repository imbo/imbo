<?php declare(strict_types=1);

namespace Imbo\Behat;

use Imagick;
use ImagickPixel;
use Imbo\Image\InputLoader\InputLoaderInterface;

class OverrideJpegDummyLoader implements InputLoaderInterface
{
    public function getSupportedMimeTypes(): array
    {
        return [
            'image/jpeg' => 'jpg',
        ];
    }

    public function load(Imagick $imagick, string $blob, string $mimeType)
    {
        $im = new Imagick();
        $im->newImage(300, 300, new ImagickPixel('white'));
        $im->setImageFormat('png');

        $imagick->readImageBlob($im->getImageBlob());
    }
}

class NullImplementation implements InputLoaderInterface
{
    public function getSupportedMimeTypes(): array
    {
        return [
            'image/jpeg' => 'jpg',
        ];
    }

    public function load(Imagick $imagick, string $blob, string $mimeType)
    {
        return false;
    }
}

return [
    'inputLoaders' => [
        'jpeg' => new OverrideJpegDummyLoader(),
        'null' => new NullImplementation(),
    ],
    'rethrowFinalException' => true,
];
