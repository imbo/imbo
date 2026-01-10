<?php declare(strict_types=1);

namespace Imbo\Behat;

use Imagick;
use ImagickDraw;
use ImagickPixel;
use Imbo\Image\InputLoader\InputLoaderInterface;

/**
 * Text .. image .. loader. Renders text to a 300x300 texture.
 *
 * This loader is an example and should never be used in production.
 */
class Text implements InputLoaderInterface
{
    public function getSupportedMimeTypes(): array
    {
        return [
            'text/plain' => 'txt',
        ];
    }

    public function load(Imagick $imagick, string $blob, string $mimeType)
    {
        $draw = new ImagickDraw();
        $draw->setFillColor(new ImagickPixel('black'));
        $draw->setFont('Liberation-Sans');

        $im = new Imagick();
        $im->newImage(300, 300, new ImagickPixel('white'));
        $im->annotateImage($draw, 10, 150, 0, $blob);
        $im->setImageFormat('png');
        $imagick->readImageBlob($im->getImageBlob());
    }
}

return [
    'inputLoaders' => [
        'text' => new Text(),
    ],
    'rethrowFinalException' => true,
];
