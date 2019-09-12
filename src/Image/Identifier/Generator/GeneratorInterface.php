<?php
namespace Imbo\Image\Identifier\Generator;

use Imbo\Model\Image;

/**
 * Image identifier generator interface
 */
interface GeneratorInterface {
    /**
     * Generate an image identifier
     *
     * @param Imbo\Model\Image $image The image to generate an image identifier for
     * @return string A valid image identifier, between 1 and 255 characters
     */
    function generate(Image $image);

    /**
     * Return a boolean indicating whether or not the generator is deterministic. Meaning
     * that it will always return the same identifier for the same image.
     *
     * @return boolean
     */
    function isDeterministic();
}
