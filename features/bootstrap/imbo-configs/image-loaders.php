<?php declare(strict_types=1);
namespace Imbo\Behat;

use Imagick;
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
        $im = imagecreatetruecolor(300, 300);
        $textColor = imagecolorallocate($im, 0x00, 0x00, 0x00);
        $backgroundColor = imagecolorallocate($im, 0xff, 0xff, 0xff);

        imagefill($im, 0, 0, $backgroundColor);
        imagestring($im, 5, 0, 100, $blob, $textColor);

        ob_start();
        imagepng($im);
        $image_data = ob_get_contents();
        ob_end_clean();

        $imagick->readImageBlob($image_data);
    }
}

return [
    'inputLoaders' => [
        'text' => new Text(),
    ],
    'rethrowFinalException' => true,
];
