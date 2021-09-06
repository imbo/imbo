<?php declare(strict_types=1);
namespace Imbo\Image\InputLoader;

use Imagick;

interface InputLoaderInterface
{
    /**
     * Get mime types supported by the loader
     *
     * Each element in the returned array represents a supported image format, with the mime types
     * as the key and the extension as the value.
     *
     * @return array<string,string>
     */
    public function getSupportedMimeTypes(): array;

    /**
     * Load data from a blob in a specific format into the provided Imagick instance.
     *
     * @param Imagick $imagick Imagick instance to populate with rasterized image data
     * @param string $blob The file being loaded as a binary blob
     * @param string $mimeType The determined mime type of the file. Will match one of the mime
     *                         types specified in `getSupportedMimeTypes()`.
     * @return mixed Return false to have the input loader manager try the next loader. All other
     *               return values (including null / void) means that the loader successfully
     *               managed to load the image.
     */
    public function load(Imagick $imagick, string $blob, string $mimeType);
}
