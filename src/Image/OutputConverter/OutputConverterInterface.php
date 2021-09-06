<?php declare(strict_types=1);
namespace Imbo\Image\OutputConverter;

use Imagick;
use Imbo\Model\Image;

interface OutputConverterInterface
{
    /**
     * Get mime types supported by the output converter
     *
     * Each element in the returned array represents a supported image format, with the
     * mime type as the key and the extension as the value.
     *
     * @return array<string,array<string>|string>
     */
    public function getSupportedMimeTypes(): array;

    /**
     * Load data from a blob in a specific format into the provided Imagick instance.
     *
     * Return false on failure.
     *
     * @param Imagick $imagick Imagick instance to populate with rasterized image data
     * @param Image $image The Image model to render from Imbo
     * @param string $extension The extension requested through imbo. Will match one of the extension specified in `getSupportedMimeTypes()`.
     * @param string $mimeType Mime type of the file being output.
     * @return null|bool|Imagick
     */
    public function convert(Imagick $imagick, Image $image, string $extension, string $mimeType = null);
}
