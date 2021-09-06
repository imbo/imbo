<?php declare(strict_types=1);
namespace Imbo\Behat;

use Imagick;
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
        $im = imagecreatetruecolor(300, 300);

        ob_start();
        imagepng($im);
        $image_data = ob_get_contents();
        ob_end_clean();

        $imagick->readImageBlob($image_data);
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
