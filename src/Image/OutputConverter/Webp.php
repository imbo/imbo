<?php
namespace Imbo\Image\OutputConverter;

use Imbo\Exception\OutputConverterException;
use Imbo\Model\Image;
use Imagick;
use ImagickException;

/**
 * Output converter for outputting Webp
 */
class Webp implements OutputConverterInterface {
    /**
     * {@inheritdoc}
     */
    public function getSupportedMimeTypes() {
        return [
            'image/webp' => 'webp',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function convert(Imagick $imagick, Image $image, $extension, $mime) {
        try {
            $imagick->setImageFormat($extension);
        } catch (ImagickException $e) {
            throw new OutputConverterException($e->getMessage(), 400, $e);
        }

        $image->hasBeenTransformed(true);
    }
}
