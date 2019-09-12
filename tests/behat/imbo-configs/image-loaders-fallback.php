<?php
use Imbo\Image\InputLoader\InputLoaderInterface;

class OverrideJpegDummyLoader implements InputLoaderInterface {
    public function getSupportedMimeTypes() {
        return [
            'image/jpeg' => 'jpg',
        ];
    }

    public function load(\Imagick $imagick, $blob, $mimeType) {
        $im = imagecreatetruecolor(300, 300);

        ob_start();
        imagepng($im);
        $image_data = ob_get_contents();
        ob_end_clean();

        $imagick->readImageBlob($image_data);
    }
}

class NullImplementation implements InputLoaderInterface {
    public function getSupportedMimeTypes() {
        return [
            'image/jpeg' => 'jpg',
        ];
    }

    public function load(\Imagick $imagick, $blob, $mimeType) {
        return false;
    }
}


/**
 * Add custom image loaders for testing
 */
return [
    'inputLoaders' => [
        'jpeg' => new OverrideJpegDummyLoader(),
        'null' => new NullImplementation(),
    ],
    'rethrowFinalException' => true,
];
