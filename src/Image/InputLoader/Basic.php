<?php
namespace Imbo\Image\InputLoader;

use Imagick;

/**
 * Basic image loader / fallback image loader
 */
class Basic implements InputLoaderInterface {
    /**
     * {@inheritdoc}
     */
    public function getSupportedMimeTypes() {
        return [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/gif' => 'gif',
            'image/tiff' => 'tif',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(Imagick $imagick, $blob, $mimeType) {
        $imagick->readImageBlob($blob);
        $imagick->setIteratorIndex(0);
    }
}
