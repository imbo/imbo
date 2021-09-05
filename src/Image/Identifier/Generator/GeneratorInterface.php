<?php declare(strict_types=1);
namespace Imbo\Image\Identifier\Generator;

use Imbo\Model\Image;

/**
 * Image identifier generator interface
 */
interface GeneratorInterface
{
    /**
     * Generate an image identifier
     *
     * @param Image $image The image to generate an image identifier for
     * @return string A valid image identifier, between 1 and 255 characters
     */
    public function generate(Image $image): string;

    /**
     * Return a bool indicating whether or not the generator is deterministic. Meaning
     * that it will always return the same identifier for the same image.
     *
     * @return bool
     */
    public function isDeterministic(): bool;
}
